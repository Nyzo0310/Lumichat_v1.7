# actions.py
"""
LumiCHAT custom actions for Rasa 3.x
- Cebuano/English date & time parsing helpers
- Reflective support action for core emotions
- Form validators for appointment booking (date/time/consent)
- Language-aware ask actions for form slots
- Submit appointment action that returns a robust booking link
"""

from typing import Any, Dict, List, Text, Optional
from datetime import datetime, timedelta
import os
import re

from rasa_sdk import Action, Tracker, FormValidationAction
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.events import SlotSet, EventType
from rasa_sdk.types import DomainDict

# -----------------------------------------------------------------------------
# Optional dependency (graceful fallback if not installed)
# -----------------------------------------------------------------------------
try:
    # pip install python-dateutil
    from dateutil import parser as dateparser  # type: ignore
except Exception:  # pragma: no cover
    dateparser = None  # we will just skip fuzzy parsing if missing


# -----------------------------------------------------------------------------
# Cebuano / English normalization maps
# -----------------------------------------------------------------------------
CEB_DATE_MAP: Dict[str, str] = {
    "ugma": "tomorrow",
    "karon": "today",
    "unya": "later",
    "sunod semana": "next week",
}

EN_MISSPELLINGS: Dict[str, str] = {
    "tommorow": "tomorrow",
    "tomorow": "tomorrow",
    "tmrw": "tomorrow",
}

CEB_TIME_KEYWORDS: Dict[str, str] = {
    "buntag": "morning",
    "hapon": "afternoon",
    "udto": "noon",
    "gabii": "evening",
    "karon": "now",
}


# -----------------------------------------------------------------------------
# Helper utilities
# -----------------------------------------------------------------------------
def _lang(meta: Dict[str, Any]) -> Text:
    """Extract UI language from message metadata (default: 'en')."""
    try:
        return str(((meta.get("lumichat") or {}).get("lang") or "en")).lower()
    except Exception:
        return "en"


def _one(lang: Text, en: Text, ceb: Text) -> Text:
    """Pick English or Cebuano text by language code."""
    return ceb if lang == "ceb" else en


def _appointment_link() -> str:
    """
    Build a robust appointment URL:
    - honors LUMICHAT_APPOINTMENT_URL if set
    - falls back to local route
    - ensures a scheme is present so it doesn't render as 'http:' only
    """
    link = os.getenv("LUMICHAT_APPOINTMENT_URL") or "http://127.0.0.1:8000/appointment"
    if not re.match(r"^https?://", link):
        link = "https://" + link.lstrip("/")
    return link


def normalize_date_text(text: str) -> str:
    """Lowercase, trim, fix misspellings, and map Cebuano date words."""
    t = (text or "").strip().lower()
    if t in EN_MISSPELLINGS:
        t = EN_MISSPELLINGS[t]
    for k, v in CEB_DATE_MAP.items():
        if k in t:
            t = t.replace(k, v)
    return t


def normalize_time_text(text: str) -> str:
    """Lowercase, map Cebuano time words, and strip spaces."""
    t = (text or "").strip().lower()
    for k, v in CEB_TIME_KEYWORDS.items():
        if k in t:
            t = v
    return t.replace(" ", "")


def parse_date(value: str) -> Optional[str]:
    """
    Parse date from free text. Returns ISO date (YYYY-MM-DD) or None.
    Accepts Cebuano phrases and common misspellings.
    """
    s = normalize_date_text(value)

    if s in {"today", "now"}:
        return datetime.now().date().isoformat()
    if s == "tomorrow":
        return (datetime.now() + timedelta(days=1)).date().isoformat()
    if s == "nextweek":
        return (datetime.now() + timedelta(days=7)).date().isoformat()

    if dateparser:
        try:
            dt = dateparser.parse(s, fuzzy=True, dayfirst=False)
            if dt:
                return dt.date().isoformat()
        except Exception:
            pass

    # Fallback: accept strings that *look* like dates (e.g., 9/7/2025 or Sept 7)
    if re.search(r"\b(?:\d{1,2}[/-]\d{1,2}(?:[/-]\d{2,4})?|[a-z]{3,}\s+\d{1,2})\b", s):
        return s

    return None


def parse_time(value: str) -> Optional[str]:
    """
    Parse time from free text. Returns 24h HH:MM or None.
    Accepts 10am / 3pm / 15:00, words (morning/noon/afternoon/evening/now),
    and Cebuano patterns like "alas 10 sa buntag".
    """
    s = normalize_time_text(value)

    # 10, 10:30, 10am, 10:30pm
    m = re.match(r"^(\d{1,2})(?::(\d{2}))?(am|pm)?$", s)
    if m:
        h = int(m.group(1))
        mins = int(m.group(2) or 0)
        ampm = m.group(3)
        if ampm:
            if ampm == "pm" and h != 12:
                h += 12
            if ampm == "am" and h == 12:
                h = 0
        if 0 <= h < 24 and 0 <= mins < 60:
            return f"{h:02d}:{mins:02d}"

    # Words
    WORD_MAP = {
        "morning": "09:00",
        "noon": "12:00",
        "afternoon": "15:00",
        "evening": "18:00",
        "now": datetime.now().strftime("%H:%M"),
    }
    if s in WORD_MAP:
        return WORD_MAP[s]

    # Cebuano: "alas 10 sa buntag/hapon/gabii"
    m2 = re.search(r"alas\s*(\d{1,2})\s*(?:sa)?\s*(buntag|hapon|gabii)", (value or "").lower())
    if m2:
        h = int(m2.group(1))
        part = m2.group(2)
        if part == "buntag":  # morning
            if h == 12:
                h = 0
        elif part in {"hapon", "gabii"}:  # afternoon/evening
            if h < 12:
                h += 12
        return f"{h:02d}:00"

    return None


def normalize_yes_no(value: Optional[str]) -> Text:
    v = (value or "").strip().lower()
    if v in {
        "yes","y","yep","yeah","yup","sure","ok","okay","okk","okie",
        "go ahead","please","yes please","proceed","sige","oo","oo, sige","oo sige"
    }:
        return "yes"
    if v in {"no","nope","nah","not now","maybe later","dili","ayaw"}:
        return "no"
    return v or ""

# -----------------------------------------------------------------------------
# Conversational actions
# -----------------------------------------------------------------------------
class ActionReflectiveSupport(Action):
    """
    Gentle reflective responses for common emotional intents.
    Triggered via rules for:
    - express_happiness, express_sadness, express_anxiety, express_stress
    """

    def name(self) -> Text:
        return "action_reflective_support"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: DomainDict) -> List[EventType]:
        intent = (tracker.latest_message.get("intent") or {}).get("name", "")
        lang = _lang(tracker.latest_message.get("metadata") or {})

        if intent == "express_happiness":
            msg = _one(lang,
                       "I'm glad to hear you’re feeling good. Anything you want to share?",
                       "Maayo kaayo nga nalingaw ka. Aduna bay gusto nimo ipa-ambit?")
        elif intent == "express_sadness":
            msg = _one(lang,
                       "I’m sorry you’re feeling down. Do you want to talk about what happened?",
                       "Pasayloa ko nga medyo mingaw imong gibati. Gusto ka mosulti unsay nahitabo?")
        elif intent == "express_anxiety":
            msg = _one(lang,
                       "That sounds stressful. Let’s take it one step at a time. What’s worrying you most?",
                       "Murag lisod gyud na. Hinay-hinay ta. Unsay pinakadako nimong kabalakan?")
        elif intent == "express_stress":
            msg = _one(lang,
                       "Thanks for sharing. Would a short breathing tip or scheduling a counselor help?",
                       "Salamat sa pag-ambit. Gusto ka og mubo nga breathing tip o magpa-iskedyul sa counselor?")
        else:
            msg = _one(lang,
                       "I’m here for you. Tell me more so I can help better.",
                       "Anaa ra ko para nimo. Sultihi ko og dugang para mas matabangan tika.")

        dispatcher.utter_message(text=msg)
        return []


# -----------------------------------------------------------------------------
# Language-aware ask actions for form slots
# -----------------------------------------------------------------------------
class ActionAskAppointmentDate(Action):
    def name(self) -> Text:
        return "action_ask_appointment_date"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: DomainDict) -> List[EventType]:
        lang = _lang(tracker.latest_message.get("metadata") or {})
        dispatcher.utter_message(text=_one(
            lang,
            "Got it. What date works for you? (e.g., today, tomorrow)",
            "Sige. Kanus-a ka gusto? (pananglitan: karon, ugma)",
        ))
        return []


class ActionAskAppointmentTime(Action):
    def name(self) -> Text:
        return "action_ask_appointment_time"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: DomainDict) -> List[EventType]:
        lang = _lang(tracker.latest_message.get("metadata") or {})
        dispatcher.utter_message(text=_one(
            lang,
            "Noted. What time works for you? (e.g., 10am)",
            "Noted. Unsa nga oras nimo gusto? (pananglitan: alas 10 sa buntag)",
        ))
        return []


class ActionAskConsent(Action):
    def name(self) -> Text:
        return "action_ask_consent"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: DomainDict) -> List[EventType]:
        lang = _lang(tracker.latest_message.get("metadata") or {})
        dispatcher.utter_message(text=_one(
            lang,
            "Before we proceed, do I have your consent to book and share details with the counselor?",
            "Sa dili pa ta magpadayon, mosugot ka ba nga i-book tika ug ipaambit ang detalye sa counselor?",
        ))
        return []


# -----------------------------------------------------------------------------
# Form validation (used by `appointment_form`)
# -----------------------------------------------------------------------------
class ValidateAppointmentForm(FormValidationAction):
    def name(self) -> Text:
        return "validate_appointment_form"

    async def validate_appointment_date(self, slot_value: Text, dispatcher: CollectingDispatcher,
                                        tracker: Tracker, domain: DomainDict) -> Dict[Text, Any]:
        lang = _lang(tracker.latest_message.get("metadata") or {})
        iso = parse_date(slot_value)
        if not iso:
            dispatcher.utter_message(text=_one(
                lang,
                "I couldn’t recognize that date. Please try something like 'tomorrow' or 'Sept 10'.",
                "Wala nako masabti ang petsa. Pananglitan: 'ugma' o 'Sept 10'.",
            ))
            return {"appointment_date": None}
        return {"appointment_date": iso}

    async def validate_appointment_time(self, slot_value: Text, dispatcher: CollectingDispatcher,
                                        tracker: Tracker, domain: DomainDict) -> Dict[Text, Any]:
        lang = _lang(tracker.latest_message.get("metadata") or {})
        hhmm = parse_time(slot_value)
        if not hhmm:
            dispatcher.utter_message(text=_one(
                lang,
                "I couldn’t recognize that time. Try '10am', '3:30pm', or 'afternoon'.",
                "Wala nako masabti ang oras. Pananglitan: 'alas 10 sa buntag' o 'hapon'.",
            ))
            return {"appointment_time": None}
        return {"appointment_time": hhmm}

    async def validate_consent(self, slot_value: Text, dispatcher: CollectingDispatcher,
                               tracker: Tracker, domain: DomainDict) -> Dict[Text, Any]:
        lang = _lang(tracker.latest_message.get("metadata") or {})
        v = normalize_yes_no(slot_value)
        if v not in {"yes", "no"}:
            dispatcher.utter_message(text=_one(
                lang,
                "Is it okay if I book an appointment for you? Please answer Yes or No.",
                "Okay ra ba nga magpa-iskedyul ko og appointment para nimo? Tubag ug Oo o Dili.",
            ))
            return {"consent": None}
        return {"consent": v}


# -----------------------------------------------------------------------------
# Submit appointment action
# -----------------------------------------------------------------------------
class ActionSubmitAppointment(Action):
    def name(self) -> Text:
        return "action_submit_appointment"

    def run(self, dispatcher, tracker, domain):
        lang = _lang(tracker.latest_message.get("metadata") or {})
        consent = normalize_yes_no(tracker.get_slot("consent"))
        if consent not in {"yes", "no"}:
            consent = normalize_yes_no((tracker.latest_message.get("text") or ""))

        if consent != "yes":
            dispatcher.utter_message(text=_one(
                lang,
                "No problem—message me anytime if you’d like to book later.",
                "Sige ra—pwede ka mo-chat kanus-a nimo gusto kung magpa-book ka sunod.",
            ))
            return [SlotSet("consent", None)]

        # IMPORTANT: send the placeholder; Laravel replaces {APPOINTMENT_LINK} with a real <a> button.
        msg_en  = "Yes, we have a counselor available. Please tap here to book: {APPOINTMENT_LINK}"
        msg_ceb = "Oo, adunay counselor nga available. I-tap diri para magpa-book: {APPOINTMENT_LINK}"
        dispatcher.utter_message(text=_one(lang, msg_en, msg_ceb))

        return [SlotSet("consent", None)]


# Exported names (handy for tests/imports)
__all__ = [
    "parse_date",
    "parse_time",
    "normalize_yes_no",
    "ActionReflectiveSupport",
    "ActionAskAppointmentDate",
    "ActionAskAppointmentTime",
    "ActionAskConsent",
    "ValidateAppointmentForm",
    "ActionSubmitAppointment",
]
