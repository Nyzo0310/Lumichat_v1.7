<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatSession;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /* =========================================================================
     | Helpers: language, risk, appointment, crisis
     * =========================================================================*/

    /**
     * Very lightweight EN vs CEB language inference for metadata.
     */
    private function inferLanguage(string $t): string
    {
        $x = mb_strtolower($t);
        $cebWords = [
            'nag','ko','kaayo','unsa','karon','gani','balaka','kulba','kapoy','nalipay',
            'gusto','pa-schedule','magpa-iskedyul','pwede','palihug','bug-at','dili',
            'maayong','kumusta','mohilak','hikog','paglaum','jud','lagi','bitaw'
        ];
        $hits = 0;
        foreach ($cebWords as $w) {
            if (str_contains($x, $w)) $hits++;
        }
        return $hits >= 2 ? 'ceb' : 'en';
    }

    /**
     * From a bilingual string like "EN … / CEB …", return one side.
     * If no slash is present, return as-is.
     */
    private function pickLanguageVariant(string $reply, string $lang): string
    {
        $parts = preg_split('/\s*\/\s*/u', $reply, 2);
        if (count($parts) === 2) {
            return ($lang === 'ceb') ? trim($parts[1]) : trim($parts[0]);
        }
        return $reply;
    }

    /**
     * Risk evaluator (English + Bisaya).
     * Returns: 'high' | 'moderate' | 'low'
     */
    private function evaluateRiskLevel(string $text): string
    {
        $t = mb_strtolower($text);
        $t = preg_replace('/\s+/u', ' ', $t ?? '');

        // HIGH
        $high = [
            // EN direct suicidality / self-harm
            '\bi\s*(?:wanna|want(?:\s*to)?|plan|planning|intend|need|will|gonna)\s*(?:to\s*)?(?:die|kill myself|end (?:it|my life)|commit suicide|unalive|disappear|be gone)\b',
            '\b(?:kill myself|commit suicide|end it all|no reason to live|life is pointless)\b',
            '\bi\s*(?:wish|want)\s*(?:i\s*)?(?:were|was)\s*dead\b',
            '\bi\s*(?:can\'?t|cannot)\s*go on\b',
            '\b(?:jump off|overdose|poison myself|hang myself)\b',
            '\b(?:self[- ]harm|cut(?:ting)? myself)\b',
            // CEB
            '\bgusto na ko mamatay\b',
            '\bmaghikog\b',
            '\bwala na koy paglaum\b',
            '\bgusto ko mawala\b',
            '\btapuson na nako tanan\b',
        ];
        foreach ($high as $p) {
            if (preg_match('/' . $p . '/iu', $t)) return 'high';
        }

        // Co-occurrence heuristic
        $acts   = ['suicide','die','unalive','kill myself','end my life','end it','jump','overdose','poison','cut','disappear','be gone','mamatay','hikog','wala na koy paglaum','mawala'];
        $intent = ['wanna','want','plan','planning','thinking','feel like','i should','i will','i might','really want','gonna','gusto','buot','tingali','murag'];
        foreach ($acts as $a) foreach ($intent as $b) {
            if (str_contains($t, $a) && str_contains($t, $b)) return 'high';
        }

        // MODERATE
        $moderate = [
            '\bi\s*(?:hate|loath|despise)\s*myself\b',
            '\b(?:i (?:want|wish) (?:to )?disappear|i (?:don\'?t|do not) want to exist|i wish i wasn\'?t here|i wish i never existed)\b',
            '\b(?:i(?:\'m| am)? (?:not ?ok(?:ay)?|empty|worthless|a burden|beyond help))\b',
            '\b(?:give up on life|i don\'?t want to live|i feel like dying)\b',
            '\b(?:depress(?:ed|ing)?|anxious|panic|overwhelmed|burnout|stressed)\b',
            // CEB
            '\bnagkabalaka ko\b',
            '\bkulba\b',
            '\bkapoy kaayo\b',
            '\bbug-at kaayo\b',
            '\bna[- ]?overwhelm\b',
            '\bdili ko okay\b',
            '\bwala koy gana\b',
        ];
        foreach ($moderate as $p) {
            if (preg_match('/' . $p . '/iu', $t)) return 'moderate';
        }

        return 'low';
    }

    /**
     * Build Rasa message metadata (used by actions via tracker.latest_message.metadata).
     */
    private function buildRasaMetadata(int $sessionId, string $lang, string $risk): array
    {
        return [
            'lumichat' => [
                'session_id' => $sessionId,
                'lang'       => $lang,   // 'en' | 'ceb'
                'risk'       => $risk,   // 'low' | 'moderate' | 'high'
                'app'        => 'lumichat-web',
            ]
        ];
    }

    /**
     * PH-friendly crisis card with a placeholder {APPOINTMENT_LINK}.
     */
    private function crisisMessageWithLink(): string
    {
        $c   = config('services.crisis');
        $emg = e($c['emergency_number'] ?? '911');
        $hn  = e($c['hotline_name'] ?? 'Hopeline PH (24/7)');
        $hp  = e($c['hotline_phone'] ?? '0917-558-4673 / (02) 804-4673');
        $ht  = e($c['hotline_text'] ?? 'Text 0917-558-4673');
        $url = e($c['hotline_url'] ?? 'https://www.facebook.com/HopelinePH/');

        return <<<HTML
<div class="space-y-2 leading-relaxed">
  <p class="font-semibold">We’re here to help. / Ania mi para motabang.</p>
  <ul class="list-disc pl-5 text-sm">
    <li>If you’re in immediate danger, call <strong>{$emg}</strong>. / Kung emerhensya, tawag sa <strong>{$emg}</strong>.</li>
    <li>24/7 support: <strong>{$hn}</strong> — call <strong>{$hp}</strong>, {$ht}, or visit
      <a href="{$url}" target="_blank" rel="noopener" class="underline">{$url}</a>.
    </li>
  </ul>
  <p class="text-sm">You can also book a time with a school counselor: / Pwede pud ka magpa-book sa counselor:</p>
  <div class="pt-1">{APPOINTMENT_LINK}</div>
</div>
HTML;
    }

    /**
     * Robust appointment intent detector (EN + Bisaya).
     */
    private function wantsAppointment(string $text): bool
    {
        return (bool) preg_match(
            '/
            (?:\b(appoint(?:ment)?|schedule|book|booking|meet|talk)\b.*\b(counsel(?:or|ling)|therap(?:ist|y)|advisor)\b)|
            (?:\bsee (?:a )?counselor\b)|
            (?:\b(counselor|therapist)\b.*\b(available|when|talk|meet)\b)|
            # Bisaya:
            (?:\bpa-?schedule\b|\bmagpa-?iskedyul\b|\bmo-?book\b).*?(?:\bcounsel(?:or|ing)?\b|\bkonselor\b|\btambag\b|\bmakig[- ]?istorya\b)
            /ix',
            $text
        );
    }

    /* =========================================================================
     | UI pages
     * =========================================================================*/

    public function index()
    {
        $userId = Auth::id();
        $showGreeting = false;

        // Validate currently active session id; clear if stale
        $activeId = session('chat_session_id');
        if ($activeId) {
            $exists = ChatSession::where('id', $activeId)->where('user_id', $userId)->exists();
            if (!$exists) {
                session()->forget('chat_session_id');
                $activeId = null;
            }
        }

        // Reuse last session if none active
        if (!$activeId) {
            $latest = ChatSession::where('user_id', $userId)->latest('updated_at')->first();
            if ($latest) {
                session(['chat_session_id' => $latest->id]);
                $activeId = $latest->id;
            }
        }

        $chats = Chat::where('user_id', $userId)
            ->when($activeId, fn($q) => $q->where('chat_session_id', $activeId))
            ->orderBy('sent_at')
            ->get()
            ->map(function ($chat) {
                try { $chat->message = \Illuminate\Support\Facades\Crypt::decryptString($chat->message); }
                catch (\Throwable $e) { $chat->message = '[Encrypted]'; }
                return $chat;
            });

        return view('chat', compact('chats', 'showGreeting'));
    }

    public function newChat(Request $request)
    {
        session()->forget('chat_session_id'); // start fresh
        return redirect()->route('chat.index');
    }

    /* =========================================================================
     | Store a user message, call Rasa, risk/booking/crisis logic
     * =========================================================================*/
    public function store(Request $request)
    {
        $request->validate(['message' => 'required|string']);

        $userId    = Auth::id();
        $sessionId = session('chat_session_id');

        // Verify session exists & belongs to user; recreate if stale/missing
        $session = null;
        if ($sessionId) {
            $session = ChatSession::where('id', $sessionId)
                ->where('user_id', $userId)
                ->first();
        }
        if (!$session) {
            $session = ChatSession::create([
                'user_id'       => $userId,
                'topic_summary' => 'Starting conversation...',
                'is_anonymous'  => 0,
                'risk_level'    => 'low',
            ]);
            session(['chat_session_id' => $session->id]);
            $this->logActivity('chat_session_created', 'New chat session auto-created', $session->id, [
                'is_anonymous' => false,
                'reused'       => false,
            ]);
        }
        $sessionId = $session->id;

        $text   = (string) $request->message;
        $lang   = $this->inferLanguage($text);
        $msgRisk = $this->evaluateRiskLevel($text);

        // Save USER message (encrypted)
        $userMsg = Chat::create([
            'user_id'         => $userId,
            'chat_session_id' => $sessionId,
            'sender'          => 'user',
            'message'         => Crypt::encryptString($text),
            'sent_at'         => now(),
        ]);

        // Update topic summary on first user message
        $count = Chat::where('chat_session_id', $sessionId)->where('sender', 'user')->count();
        if ($count === 1) {
            preg_match('/\b(sad|depress|help|anxious|angry|lonely|stress|tired|happy|excited|not okay|nagool|kapoy|kulba|nalipay)\b/i', $text, $m);
            $summary = $m[0] ?? Str::limit($text, 40, '…');
            $session->update(['topic_summary' => ucfirst($summary)]);
        }

        // Send to Rasa with metadata
        $rasaUrl  = config('services.rasa.url', env('RASA_URL', 'http://127.0.0.1:5005/webhooks/rest/webhook'));
        $metadata = $this->buildRasaMetadata($sessionId, $lang, $msgRisk);
        $botReplies = [];

        try {
            $r = Http::timeout(8)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($rasaUrl, [
                    'sender'   => 'u_' . $userId . '_s_' . $sessionId,
                    'message'  => $text,
                    'metadata' => $metadata,
                ]);

            if ($r->ok()) {
                $payload = $r->json() ?? [];
                foreach ($payload as $piece) {
                    if (!empty($piece['text'])) {
                        $botReplies[] = $piece['text'];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Network/timeout fallback (bilingual; filtered later)
            $botReplies = [
                "It’s okay to feel that way. I’m here to listen. Would you like to share more? / Sige ra na, ania ko maminaw. Gusto nimo isulti pa ug dugang?"
            ];
        }

        if (empty($botReplies)) {
            $botReplies = [
                "I didn’t quite get that, but I’m here to listen. Could you say it another way? / Wala kaayo nako masabti, pero ania ko maminaw. Pwede nimo usbon pagpasabot?"
            ];
        }

        // Update session risk if needed
        $current = $session->risk_level ?: 'low';
        $order   = ['low' => 0, 'moderate' => 1, 'high' => 2];
        $new     = ($order[$msgRisk] > $order[$current]) ? $msgRisk : $current;
        if ($new !== $current) {
            $session->update(['risk_level' => $new]);
        }
        $this->logActivity('risk_detected', "Risk level: {$msgRisk}", $sessionId, [
            'risk_level'      => $msgRisk,
            'message_preview' => Str::limit($text, 120),
        ]);

        // Crisis block (once per session)
        $crisisAlreadyShown = session('crisis_prompted_for_session_' . $sessionId, false);
        if (!$crisisAlreadyShown && $msgRisk === 'high') {
            session(['crisis_prompted_for_session_' . $sessionId => true]);
            $this->logActivity('crisis_prompt', 'Crisis resources displayed', $sessionId, null);
            array_unshift($botReplies, $this->crisisMessageWithLink());
        }

        // Build signed link (feature gate) for {APPOINTMENT_LINK}
        $signed  = URL::signedRoute('features.enable_appointment');
        $ctaHtml = '<a href="' . $signed . '" class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">Book an appointment</a>';

        // Save BOT replies (encrypted) — enforce monolingual output
        $botPayload = [];
        foreach ($botReplies as $reply) {
            if (is_string($reply) && str_contains($reply, '{APPOINTMENT_LINK}')) {
                $reply = str_replace('{APPOINTMENT_LINK}', $ctaHtml, $reply);
            }

            // Monolingual filter
            $reply = $this->pickLanguageVariant($reply, $lang);

            $bot = Chat::create([
                'user_id'         => $userId,
                'chat_session_id' => $sessionId,
                'sender'          => 'bot',
                'message'         => Crypt::encryptString($reply),
                'sent_at'         => now(),
            ]);

            $botPayload[] = [
                'text'       => $reply,
                'time_human' => $bot->sent_at->timezone(config('app.timezone'))->format('H:i'),
                'sent_at'    => $bot->sent_at->toIso8601String(),
            ];
        }

        return response()->json([
            'user_message' => [
                'text'       => $text,
                'time_human' => $userMsg->sent_at->timezone(config('app.timezone'))->format('H:i'),
                'sent_at'    => $userMsg->sent_at->toIso8601String(),
            ],
            'bot_reply' => $botPayload,
        ]);
    }

    /* =========================================================================
     | History utilities
     * =========================================================================*/

    public function history(Request $request)
    {
        $q = trim($request->get('q', ''));

        $sessions = ChatSession::with(['chats' => function ($query) {
                $query->latest('sent_at')->limit(1);
            }])
            ->where('user_id', Auth::id())
            ->when($q !== '', fn($query) => $query->where('topic_summary', 'like', "%{$q}%"))
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        foreach ($sessions as $session) {
            foreach ($session->chats as $chat) {
                try {
                    $chat->message = Crypt::decryptString($chat->message);
                } catch (\Throwable $e) {
                    $chat->message = '[Unreadable]';
                }
            }
        }

        return view('chat-history', compact('sessions', 'q'));
    }

    public function viewSession($id)
    {
        $session = ChatSession::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $messages = Chat::where('chat_session_id', $id)
            ->where('user_id', Auth::id())
            ->orderBy('sent_at')
            ->get()
            ->map(function ($c) {
                try {
                    $c->message = Crypt::decryptString($c->message);
                } catch (\Throwable $e) {
                    $c->message = '[Unreadable]';
                }
                return $c;
            });

        return view('chat-view', compact('session', 'messages'));
    }

    public function deleteSession($id)
    {
        ChatSession::where('id', $id)->where('user_id', Auth::id())->delete();

        // Clear active browser session if it matches the deleted one
        if ((int) session('chat_session_id') === (int) $id) {
            session()->forget('chat_session_id');
        }

        return redirect()->route('chat.history')->with('status', 'Session deleted');
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', (string)$request->input('ids', ''))));
        if (!empty($ids)) {
            ChatSession::where('user_id', Auth::id())
                ->whereIn('id', $ids)
                ->delete();

            // Clear if the active one was among those deleted
            if (in_array((int) session('chat_session_id'), $ids, true)) {
                session()->forget('chat_session_id');
            }
        }
        return redirect()->route('chat.history')->with('status', 'Selected sessions deleted');
    }

    public function activate($id)
    {
        $session = ChatSession::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        session(['chat_session_id' => $session->id]);
        $session->touch();
        return redirect()->route('chat.index')->with('status', 'session-activated');
    }

    /* =========================================================================
     | Activity logger
     * =========================================================================*/
    private function logActivity(string $event, string $description, int $sessionId, ?array $meta = null): void
    {
        try {
            ActivityLog::create([
                'event'        => $event,
                'description'  => $description,
                'actor_id'     => Auth::id(),
                'subject_type' => ChatSession::class,
                'subject_id'   => $sessionId,
                'meta'         => $meta,
            ]);
        } catch (\Throwable $e) {
            // best-effort only
        }
    }
}
