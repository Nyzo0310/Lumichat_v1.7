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
     | Risk helpers
     * =========================================================================*/

    private function evaluateRiskLevel(string $text): string
    {
        $t = mb_strtolower($text);
        $t = preg_replace('/\s+/u', ' ', $t ?? '');

        $high = [
            '\bi\s*(?:wanna|want(?:\s*to)?|plan|planning|intend|need|will|gonna)\s*(?:to\s*)?(?:die|kill myself|end (?:it|my life)|commit suicide|unalive|disappear|be gone)\b',
            '\b(?:kill myself|commit suicide|end it all|no reason to live|life is pointless)\b',
            '\bi\s*(?:wish|want)\s*(?:i\s*)?(?:were|was)\s*dead\b',
            '\bi\s*(?:can\'?t|cannot)\s*go on\b',
            '\b(?:jump off|overdose|poison myself|hang myself)\b',
            '\b(?:self[- ]harm|cut(?:ting)? myself)\b',
        ];
        foreach ($high as $p) {
            if (preg_match('/' . $p . '/iu', $t)) return 'high';
        }

        $acts   = ['suicide','die','unalive','kill myself','end my life','end it','jump','overdose','poison','cut','disappear','be gone'];
        $intent = ['wanna','want','plan','planning','thinking','feel like','i should','i will','i might','really want','gonna'];
        foreach ($acts as $a) foreach ($intent as $b) {
            if (str_contains($t, $a) && str_contains($t, $b)) return 'high';
        }

        $moderate = [
            '\bi\s*(?:hate|loath|despise)\s*myself\b',
            '\b(?:i (?:want|wish) (?:to )?disappear|i (?:don\'?t|do not) want to exist|i wish i wasn\'?t here|i wish i never existed)\b',
            '\b(?:i(?:\'m| am)? (?:not ?ok(?:ay)?|empty|worthless|a burden|beyond help))\b',
            '\b(?:give up on life|i don\'?t want to live|i feel like dying)\b',
            '\b(?:depress(?:ed|ing)?|anxious|panic|overwhelmed)\b',
        ];
        foreach ($moderate as $p) {
            if (preg_match('/' . $p . '/iu', $t)) return 'moderate';
        }

        return 'low';
    }

    /** Lightweight mood guess to prefill the quick self-assessment */
    private function guessMoodFromText(string $text): string
    {
        $t = mb_strtolower($text);

        $sad      = ['sad','down','depress','cry','empty','lonely','blue','worthless','gloom'];
        $anxious  = ['anxious','anxiety','panic','nervous','worry','worried','scared','afraid','overthink'];
        $stressed = ['stress','stressed','overwhelmed','burnout','tired','exhausted','pressure'];
        $happy    = ['happy','grateful','excited','good','great','fine','okay','ok'];

        $score = ['Happy'=>0,'Sad'=>0,'Anxious'=>0,'Stressed'=>0];

        foreach ($sad as $w)      if (str_contains($t, $w)) $score['Sad']++;
        foreach ($anxious as $w)  if (str_contains($t, $w)) $score['Anxious']++;
        foreach ($stressed as $w) if (str_contains($t, $w)) $score['Stressed']++;
        foreach ($happy as $w)    if (str_contains($t, $w)) $score['Happy']++;

        arsort($score);
        $top = array_key_first($score);
        return $score[$top] > 0 ? $top : 'Neutral';
    }

    private function crisisMessageWithLink(): string
    {
        $c   = config('services.crisis');
        $emg = e($c['emergency_number'] ?? '911');
        $hn  = e($c['hotline_name'] ?? '988 Suicide & Crisis Lifeline');
        $hp  = e($c['hotline_phone'] ?? '988');
        $ht  = e($c['hotline_text'] ?? 'Text HOME to 741741');
        $url = e($c['hotline_url'] ?? 'https://988lifeline.org/');

        return <<<HTML
<div class="space-y-2 leading-relaxed">
  <p class="font-semibold">We’re here to help.</p>
  <ul class="list-disc pl-5 text-sm">
    <li>If you’re in immediate danger, call <strong>{$emg}</strong>.</li>
    <li>24/7 support: <strong>{$hn}</strong> — call <strong>{$hp}</strong>, {$ht}, or visit
      <a href="{$url}" target="_blank" rel="noopener" class="underline">{$url}</a>.
    </li>
  </ul>
  <p class="text-sm">If you want to talk with someone safe from school right now, you can book a time with a counselor:</p>
  <div class="pt-1">
    {APPOINTMENT_LINK}
  </div>
</div>
HTML;
    }

    /* =========================================================================
     | UI pages
     * =========================================================================*/

    public function index()
    {
        $userId = Auth::id();

        // Gate: Self-Assessment must be done once per PHP session
        if (!session('sa_done', false)) {
            return redirect()->route('self-assessment.create');
        }

        // Always render chat (no greeting overlay page anymore)
        $showGreeting = false;

        // Ensure we have an active session ID if you want to resume; otherwise it's okay to wait
        if (!session('chat_session_id')) {
            $latest = ChatSession::where('user_id', $userId)->latest('updated_at')->first();
            if ($latest) session(['chat_session_id' => $latest->id]);
        }

        $chats = Chat::where('user_id', $userId)
            ->when(session('chat_session_id'), fn($q) => $q->where('chat_session_id', session('chat_session_id')))
            ->orderBy('sent_at')
            ->get()
            ->map(function ($chat) {
                try { $chat->message = \Illuminate\Support\Facades\Crypt::decryptString($chat->message); }
                catch (\Throwable $e) { $chat->message = '[Encrypted]'; }
                return $chat;
            });

        return view('chat', compact('chats', 'showGreeting'));
    }

    /** "New Chat" → reset SA and route to Self-Assessment; session gets created after first message. */
    public function newChat(Request $request)
    {
        session()->forget('chat_session_id'); // start fresh
        session()->forget('sa_done');         // force SA again
        return redirect()->route('self-assessment.create');
    }

    /* =========================================================================
     | Store a user message, call Rasa, risk/booking/crisis logic
     * =========================================================================*/
    public function store(Request $request)
    {
        $request->validate(['message' => 'required|string']);

        $sessionId = session('chat_session_id');

        // If no active session yet (e.g., coming from greeting), create one now.
        if (!$sessionId) {
            $s = ChatSession::create([
                'user_id'       => Auth::id(),
                'topic_summary' => 'Starting conversation...',
                'is_anonymous'  => 0,
                'risk_level'    => 'low',
            ]);
            $sessionId = $s->id;
            session(['chat_session_id' => $sessionId]);

            $this->logActivity('chat_session_created', 'New chat session auto-created', $sessionId, [
                'is_anonymous' => false,
                'reused'       => false,
            ]);
        }

        $text = (string) $request->message;

        // Save user message (encrypted)
        $userMsg = Chat::create([
            'user_id'         => Auth::id(),
            'chat_session_id' => $sessionId,
            'sender'          => 'user',
            'message'         => Crypt::encryptString($text),
            'sent_at'         => now(),
        ]);

        // If first user message, update topic_summary
        $count = Chat::where('chat_session_id', $sessionId)->where('sender', 'user')->count();
        if ($count === 1) {
            preg_match('/\b(sad|depress|help|anxious|angry|lonely|stress|tired|happy|excited|not okay)\b/i', $text, $m);
            $summary = $m[0] ?? Str::limit($text, 40, '…');
            ChatSession::find($sessionId)?->update(['topic_summary' => ucfirst($summary)]);
        }

        // --- Rasa
        $rasaUrl    = config('services.rasa.url', env('RASA_URL', 'http://127.0.0.1:5005/webhooks/rest/webhook'));
        $botReplies = [];
        try {
            $r = Http::timeout(7)->post($rasaUrl, [
                'sender'  => 'u_' . Auth::id() . '_s_' . $sessionId,
                'message' => $text,
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
            $botReplies = ["It’s normal to feel that way. I’m here to listen. Would you like to share what happened?"];
        }

        // --- Risk evaluation & promote session risk (never downgrade)
        $session = ChatSession::find($sessionId);
        $msgRisk = $this->evaluateRiskLevel($text);

        // Decide if we should suggest the self-assessment (in-chat) — kept for future use
        $shouldSuggestAssessment =
            ($msgRisk === 'moderate' || $msgRisk === 'high') ||
            (bool) preg_match('/\b(sad|stressed|anxious|anxiety|overwhelmed|depress|lonely|hopeless|panic)\b/i', $text);

        if ($session) {
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
        }

        // --- Crisis block (once per session) for HIGH
        $crisisAlreadyShown = session('crisis_prompted_for_session_' . $sessionId, false);
        if (!$crisisAlreadyShown && $msgRisk === 'high') {
            session(['crisis_prompted_for_session_' . $sessionId => true]);
            $this->logActivity('crisis_prompt', 'Crisis resources displayed', $sessionId, null);
            array_unshift($botReplies, $this->crisisMessageWithLink());
        }

        // --- Appointment CTA intent
        $wantsAppointment = (bool) preg_match(
            '/\b(appoint|appointment|schedule|book|booking|meet|talk)\b.*\b(counsel|counselor|therap|advisor)\b|'
            . '\bsee (?:a )?counselor\b|'
            . '\b(counselor|therapist)\b.*\b(available|when|talk|meet)\b/i',
            $text
        );

        $alreadyHasLink = collect($botReplies)->contains(function ($r) {
            return is_string($r) && str_contains($r, '{APPOINTMENT_LINK}');
        });

        if ($wantsAppointment && !$alreadyHasLink) {
            if (Auth::user()->appointments_enabled ?? false) {
                $directBtn = '<a href="' . route('appointment.index') . '" class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">Book an appointment</a>';
                $botReplies[] = 'Book a session with a counselor:<br>' . $directBtn;
            } else {
                $botReplies[] = "Book a session with a counselor:<br>{APPOINTMENT_LINK}";
            }
        }

        $signed  = URL::signedRoute('features.enable_appointment');
        $ctaHtml = '<a href="' . $signed . '" class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">Book an appointment</a>';

        $botPayload = [];
        foreach ($botReplies as $reply) {
            if (is_string($reply) && str_contains($reply, '{APPOINTMENT_LINK}')) {
                $reply = str_replace('{APPOINTMENT_LINK}', $ctaHtml, $reply);
            }

            $bot = Chat::create([
                'user_id'         => Auth::id(),
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

        // Final JSON (front-end uses self_assessment meta — kept for compatibility)
        return response()->json([
            'user_message' => [
                'text'       => $text,
                'time_human' => $userMsg->sent_at->timezone(config('app.timezone'))->format('H:i'),
                'sent_at'    => $userMsg->sent_at->toIso8601String(),
            ],
            'bot_reply' => $botPayload,

            'self_assessment' => [
                'suggest'    => $shouldSuggestAssessment,
                'url'        => url('/self-assessment'),
                'risk'       => $msgRisk,
                'mood_guess' => $this->guessMoodFromText($text),
            ],
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
        return redirect()->route('chat.history')->with('status', 'Session deleted');
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter(explode(',', $request->input('ids', '')));
        if ($ids) {
            ChatSession::whereIn($ids ? ['id' => $ids] : [])->where('user_id', Auth::id())->delete();
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
