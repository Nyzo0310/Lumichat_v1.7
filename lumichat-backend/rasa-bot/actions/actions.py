from typing import Any, Dict, List, Text, Optional
from datetime import datetime, timedelta
import re

from rasa_sdk import FormValidationAction
from rasa_sdk.types import DomainDict
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk import Tracker
from rasa_sdk.events import SlotSet

try:
    from dateutil import parser as dateparser  # python-dateutil
except Exception:
    dateparser = None

# --- helper maps ---
CEB_DATE_MAP = {
    "ugma": "tomorrow",
    "karon": "today",
    "unya": "later",
    "sunod semana": "next week",
}

EN_MISSPELLINGS = {
    "tommorow": "tomorrow",
    "tomorow": "tomorrow",
    "tmrw": "tomorrow",
}

CEB_TIME_KEYWORDS = {
    "buntag": "morning",
    "hapon": "afternoon",
    "udto": "noon",
    "gabii": "evening",
    "karon": "now",
}

def normalize_date_text(text: str) -> str:
    t = (text or "").strip().lower()
    if t in EN_MISSPELLINGS:
        t = EN_MISSPELLINGS[t]
    for k, v in CEB_DATE_MAP.items():
        if k in t:
            t = t.replace(k, v)
    return t

def normalize_time_text(text: str) -> str:
    t = (text or "").strip().lower()
    for k, v in CEB_TIME_KEYWORDS.items():
        if k in t:
            t = v
    t = t.replace(" ", "")
    return t

def parse_date(value: str) -> Optional[str]:
    s = normalize_date_text(value)
    if s in {"today", "now"}:
        return datetime.now().date().isoformat()
    if s == "tomorrow":
        return (datetime.now() + timedelta(days=1)).date().isoformat()
    if s in {"nextweek"}:
        return (datetime.now() + timedelta(days=7)).date().isoformat()
    if dateparser:
        try:
            dt = dateparser.parse(s, fuzzy=True, dayfirst=False)
            if dt:
                return dt.date().isoformat()
        except Exception:
            pass
    # last resort: accept as free text if looks like a date
    if re.search(r"\b(?:\d{1,2}[/-]\d{1,2}(?:[/-]\d{2,4})?|[a-z]{3,}\s+\d{1,2})\b", s):
        return s
    return None

def parse_time(value: str) -> Optional[str]:
    s = normalize_time_text(value)
    # accept 10am / 3pm / 15:00
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
    # words like morning/afternoon/evening
    MAP = {"morning": "09:00", "noon": "12:00", "afternoon": "15:00", "evening": "18:00", "now": datetime.now().strftime("%H:%M")}
    if s in MAP:
        return MAP[s]
    # Cebuano phrases like "alas 10 sa buntag"
    m2 = re.search(r"alas\s*(\d{1,2})\s*(?:sa)?\s*(buntag|hapon|gabii)", value.lower())
    if m2:
        h = int(m2.group(1))
        part = m2.group(2)
        if part == "buntag":  # morning
            if h == 12: h = 0
        elif part == "hapon":  # afternoon
            if h < 12: h += 12
        elif part == "gabii":  # evening
            if h < 12: h += 12
        return f"{h:02d}:00"
    return None

class ActionSubmitAppointment(Action):
    def name(self) -> Text:
        return "action_submit_appointment"

    def run(self, dispatcher, tracker, domain):
        meta = tracker.latest_message.get("metadata") or {}
        lang = ((meta.get("lumichat") or {}).get("lang") or "en").lower()

        def one(en, ceb): return ceb if lang == "ceb" else en

        consent = (tracker.get_slot("consent") or "").strip().lower()
        if consent not in {"yes","no"}:
            consent = "yes" if consent in {"yes","y","oo","sige"} else "no"

        if consent != "yes":
            dispatcher.utter_message(text=one(
                "No problem—message me anytime if you’d like to book later.",
                "Sige ra—pwede ka mo-chat kanus-a nimo gusto kung magpa-book ka sunod."
            ))
            return [SlotSet("consent", None)]

        link = "http://127.0.0.1:8000/appointment"  # or your route('appointment.index')
        dispatcher.utter_message(text=one(
            f"Yes, we have a counselor available. Please check this link for availability: {link}",
            f"Oo, adunay counselor nga available. Palihug tan-awa ang link para sa availability: {link}"
        ))
        return [SlotSet("consent", None)]

