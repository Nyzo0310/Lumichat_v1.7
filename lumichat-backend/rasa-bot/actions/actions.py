import os, re, time
import pandas as pd
from typing import Any, Text, Dict, List, Optional
from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.events import SlotSet

# ---------------- CONFIG ----------------
DATASET_DIR  = os.getenv("DATASET_DIR", "data")
DATASET_PATH = os.getenv("DATASET_PATH", "FOR VALIDATION.xlsx")
ENABLE_APPROVAL_GATE = os.getenv("ENABLE_APPROVAL_GATE", "0") in ("1", "true", "TRUE", "yes", "YES")

# Column candidates (we'll auto-detect)
CAND_INTENT    = ["intent", "intent/mood", "mood", "category", "issue"]
CAND_EXAMPLES  = ["examples", "promt", "prompt", "question", "q", "user_prompt", "eng_promt"]
CAND_RESP_EN   = ["en_response", "response", "answer", "a", "eng_response"]
CAND_RESP_CEB  = ["ceb_response", "ceb", "bisaya"]
CAND_RISK      = ["risk_level", "risk"]
CAND_APPROVED  = ["approved", "approved (✔/✖)", "approved (✓/✗)", "approved (yes/no)"]

# Intent -> default risk (only low/mid stored in sheet; "high" is set by safety override)
INTENT_TO_RISK = {
    "anxiety": "low",
    "stress": "low",
    "academic_pressure": "low",
    "relationship_issues": "low",
    "low_self_esteem": "low",
    "loneliness": "low",
    "sleep_problems": "low",
    "time_management": "low",
    "financial_stress": "low",
    "depression": "mid",
    "burnout": "mid",
    "substance_abuse": "mid",
    "family_problems": "mid",
    "bullying": "mid",
    "grief_loss": "mid",
    "grief": "mid",
    "loss": "mid",
}

# High-risk regex patterns (override everything)
RED_FLAG_PATTERNS = [
    r"\b(kill|end)\s*(myself|me|my\s*life)\b",
    r"\b(suicide|suicidal|overdos(e|ed)|\bod\b)\b",
    r"\b(hurt)\s*(myself|someone|others|him|her|them)\b",
    r"\b(goodbye\s*note)\b",
    r"\b(plan)\s*(to)\s*(die|kill|end)\b",
    r"\b(partner|father|mother)\s*(hits|hurts|abuses)\s*me\b",
    r"\b(sexual\s*abuse|rape)\b",
    r"\b(threaten(ed)?|threats?)\s*(to)\s*(kill|hurt)\b",
]

def _full_path() -> str:
    p = os.path.join(DATASET_DIR, DATASET_PATH)
    return p if os.path.exists(p) else DATASET_PATH

def _load_dataframe(path: str) -> pd.DataFrame:
    ext = os.path.splitext(path)[1].lower()
    if ext in (".xlsx", ".xls"):
        df = pd.read_excel(path)
    elif ext == ".csv":
        df = pd.read_csv(path)
    else:
        raise ValueError(f"Unsupported file extension: {ext}. Use .csv or .xlsx")

    # normalize headers
    df.columns = [str(c).strip().lower() for c in df.columns]
    # keep text
    for c in df.columns:
        df[c] = df[c].apply(lambda x: "" if pd.isna(x) else str(x))
    # sanity check: need prompts + response at least
    if not any(c in df.columns for c in CAND_EXAMPLES):
        raise ValueError(f"Dataset must contain a prompt/examples column: {CAND_EXAMPLES}")
    if not any(c in df.columns for c in CAND_RESP_EN):
        raise ValueError(f"Dataset must contain a response column: {CAND_RESP_EN}")
    return df

def _get_first_col(df: pd.DataFrame, candidates: List[str]) -> Optional[str]:
    for c in candidates:
        if c in df.columns:
            return c
    return None

def _split_examples(cell: Any) -> List[str]:
    # supports "a | b | c" or ["a","b","c"] (if you later move to JSON lists)
    if isinstance(cell, list):
        return [str(s).strip().lower() for s in cell if str(s).strip()]
    text = str(cell or "")
    return [s.strip().lower() for s in text.split("|") if s.strip()]

def detect_red_flags(text: str) -> bool:
    t = (text or "").lower()
    return any(re.search(p, t) for p in RED_FLAG_PATTERNS)

class ActionSafetyProtocol(Action):
    def name(self) -> Text:
        return "action_safety_protocol"

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]):
        # English crisis message (add a CEB variant if you want)
        msg = (
            "Thank you for telling me. Your safety matters a lot. "
            "If you’re in immediate danger or have a plan to harm yourself or others, "
            "please contact local emergency services or go to a safe place now. "
            "Would you like me to help you reach a trusted person or a counselor?"
        )
        dispatcher.utter_message(text=msg)
        return [SlotSet("risk_level", "high")]

class ActionRespondFromSheet(Action):
    def name(self) -> Text:
        return "action_respond_from_sheet"

    def __init__(self) -> None:
        self._path = _full_path()
        self._df = _load_dataframe(self._path)
        self._mtime = os.path.getmtime(self._path)

        # resolve columns
        self._col_examples = _get_first_col(self._df, CAND_EXAMPLES)
        self._col_resp_en  = _get_first_col(self._df, CAND_RESP_EN)
        self._col_resp_ceb = _get_first_col(self._df, CAND_RESP_CEB)
        self._col_intent   = _get_first_col(self._df, CAND_INTENT)
        self._col_risk     = _get_first_col(self._df, CAND_RISK)
        self._col_approved = _get_first_col(self._df, CAND_APPROVED)

    def _maybe_reload(self) -> None:
        try:
            path = _full_path()
            mtime = os.path.getmtime(path)
            if path != self._path or mtime != self._mtime:
                self._path = path
                self._df = _load_dataframe(self._path)
                self._mtime = mtime
                self._col_examples = _get_first_col(self._df, CAND_EXAMPLES)
                self._col_resp_en  = _get_first_col(self._df, CAND_RESP_EN)
                self._col_resp_ceb = _get_first_col(self._df, CAND_RESP_CEB)
                self._col_intent   = _get_first_col(self._df, CAND_INTENT)
                self._col_risk     = _get_first_col(self._df, CAND_RISK)
                self._col_approved = _get_first_col(self._df, CAND_APPROVED)
        except Exception:
            # keep serving last good copy
            pass

    def run(self, dispatcher: CollectingDispatcher, tracker: Tracker, domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        self._maybe_reload()

        user_text_raw = (tracker.latest_message.get("text") or "").strip()
        user_text = user_text_raw.lower()

        if not user_text:
            dispatcher.utter_message(text="Hello! How can I help you today?")
            return []

        # 1) SAFETY FIRST — hard override
        if detect_red_flags(user_text):
            return ActionSafetyProtocol().run(dispatcher, tracker, domain)

        # language preference (CEB if slot == 'ceb', otherwise EN)
        lang = (tracker.get_slot("lang") or "en").strip().lower()

        # 2) Match against examples/prompts
        for _, row in self._df.iterrows():
            examples = _split_examples(row.get(self._col_examples, ""))
            if any(ex and ex in user_text for ex in examples):
                # Optional approval gate
                if ENABLE_APPROVAL_GATE and self._col_approved:
                    val = (row.get(self._col_approved) or "").strip().lower()
                    is_ok = val in ("✔", "✓", "yes", "y", "true", "approved")
                    if not is_ok:
                        dispatcher.utter_message(
                            text="Thanks for sharing. I’ll forward this for review. Can you tell me a bit more about what’s going on?"
                        )
                        # still set risk slot if we can infer it
                        inferred_risk = self._infer_risk(row)
                        return [SlotSet("risk_level", inferred_risk)]

                # Choose response language
                reply = None
                if lang == "ceb" and self._col_resp_ceb and row.get(self._col_resp_ceb, "").strip():
                    reply = str(row.get(self._col_resp_ceb)).strip()
                elif self._col_resp_en and row.get(self._col_resp_en, "").strip():
                    reply = str(row.get(self._col_resp_en)).strip()
                else:
                    reply = "I'm not sure how to respond to that yet."

                dispatcher.utter_message(text=reply)
                # set risk level slot (sheet value or intent fallback)
                risk_val = self._infer_risk(row)
                return [SlotSet("risk_level", risk_val)]

        # 3) No match
        dispatcher.utter_message(text="I’m not sure how to respond to that yet. Could you rephrase or share a bit more?")
        return []

    def _infer_risk(self, row: pd.Series) -> str:
        # prefer sheet's risk_level if present
        if self._col_risk:
            v = (row.get(self._col_risk) or "").strip().lower()
            if v in ("low", "mid", "high"):
                return v if v in ("low", "mid") else "high"
            # normalize older naming
            if v in ("mild",): return "low"
            if v in ("moderate","mod"): return "mid"
            if v in ("severe",): return "high"

        # else infer from intent (low/mid)
        intent_val = (row.get(self._col_intent) or "").strip().lower().replace(" ", "_")
        return INTENT_TO_RISK.get(intent_val, "low")
