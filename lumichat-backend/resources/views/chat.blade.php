@extends('layouts.app')
@section('title', 'Chat')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="px-4 sm:px-6">
  <div class="mx-auto w-full max-w-5xl h-[80vh]">

    {{-- ===================== Chat Panel ===================== --}}
    <div id="chat-wrapper"
         class="chat-panel card-shell rounded-2xl overflow-hidden flex flex-col w-full chat-appear"
         style="height:80vh">

      {{-- ===================== Header ===================== --}}
      <div class="chat-header flex items-center gap-3 bg-gradient-to-r from-indigo-600 to-purple-600
                  text-white px-5 py-3 shadow">
        <img src="{{ asset('images/chatbot.png') }}" class="w-6 h-6" alt="Bot">
        <div class="min-w-0">
          <strong class="text-lg leading-tight">LumiCHAT Assistant</strong>
          <div class="text-xs text-white/80 hidden sm:block">Friendly support that respects your privacy</div>
        </div>

        {{-- Right: typing indicator (2s minimum display) --}}
        <div id="bot-typing"
             class="ml-auto hidden items-center gap-2 select-none"
             aria-live="polite" aria-atomic="true">
          <span class="inline-flex items-center gap-1">
            <span class="dot w-2 h-2 rounded-full bg-white/90"></span>
            <span class="dot w-2 h-2 rounded-full bg-white/80"></span>
            <span class="dot w-2 h-2 rounded-full bg-white/70"></span>
          </span>
          <span class="text-xs sm:text-sm text-white/90">LumiCHAT is typing…</span>
        </div>
      </div>

      {{-- ===================== Messages ===================== --}}
      <div id="chat-messages"
           class="flex-1 min-h-0 flex flex-col gap-3 p-4 overflow-y-auto bg-gray-50 dark:bg-gray-900">
        @foreach ($chats as $chat)
          @php($mine = $chat->sender !== 'bot')
          {{-- Flex column row; side alignment is on the ROW, not the bubble --}}
          <div class="msg-row flex flex-col w-full min-w-0 {{ $mine ? 'items-end text-right' : 'items-start' }}">
            <div class="bubble {{ $mine ? 'bubble-user' : 'bubble-ai' }} px-4 py-2 rounded-2xl text-sm text-left">
              {{ $chat->message }}
            </div>
            <div class="msg-time text-[10px] text-gray-400 dark:text-gray-500 mt-1">
              {{ \Carbon\Carbon::parse($chat->sent_at ?? $chat->created_at)->format('H:i') }}
            </div>
          </div>
        @endforeach
      </div>

      {{-- ===================== Composer ===================== --}}
      <form id="chat-form"
            class="px-4 py-3 border-t bg-white dark:bg-gray-800 dark:border-gray-700">
        @csrf
        <input type="hidden" id="idem" name="_idem" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

        <div class="group relative flex items-center h-12 rounded-full bg-white dark:bg-gray-800
                    ring-1 ring-indigo-200 dark:ring-gray-700 focus-within:ring-2 focus-within:ring-indigo-400
                    transition shadow-sm">

          <textarea id="chat-message" name="message" maxlength="2000" rows="1" enterkeyhint="send"
            class="flex-1 h-full px-4 py-2 pr-[7.5rem] bg-transparent border-0 rounded-l-full
                   focus:outline-none focus:ring-0 focus:border-0 focus:shadow-none
                   placeholder:text-gray-400 dark:placeholder-gray-500 resize-none"
            placeholder="Type your message..." autocomplete="off" required></textarea>

          <div id="char-counter"
               class="absolute right-24 top-1/2 -translate-y-1/2 text-[11px] text-gray-400 select-none">
            0/2000
          </div>

          <button id="sendBtn" disabled
            class="btn-primary absolute right-1.5 top-1/2 -translate-y-1/2 h-9 px-4 rounded-full
                   disabled:opacity-50 disabled:pointer-events-none">
            Send
          </button>
        </div>
      </form>
    </div>

    <p class="chat-footer-note text-center text-gray-400 dark:text-gray-500 text-xs mt-3">
      Your conversations are encrypted and private.
    </p>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
  const messages    = document.getElementById('chat-messages');
  const form        = document.getElementById('chat-form');
  const input       = document.getElementById('chat-message');
  const counter     = document.getElementById('char-counter');
  const sendBtn     = document.getElementById('sendBtn');
  const idemEl      = document.getElementById('idem');
  const botTyping   = document.getElementById('bot-typing');
  const chatWrapper = document.getElementById('chat-wrapper');

  const STORE_URL = @json(route('chat.store'));
  const MAXLEN = 2000;

  // ---- Panel exit (optional, keep your animations)
  document.querySelectorAll('a[href*="chat/new"]').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      chatWrapper?.classList.remove('chat-appear');
      chatWrapper?.classList.add('chat-disappear');
      setTimeout(() => { window.location = link.href; }, 900);
    });
  });

  // ---- Helpers
  const INVISIBLE_RE = /[\u200B\u200C\u200D\u2060\uFEFF]/g;
  const URL_RE = /(https?:\/\/[^\s<>"']+)/gi;

  function sanitizeClientSide(raw) { return (raw || '').replace(INVISIBLE_RE, '').replace(/\s+/g, ' ').trim(); }
  function linkifyText(text) { return String(text).replace(URL_RE, m => `<a href="${m}" target="_blank" rel="noopener noreferrer">${m}</a>`); }

  function sanitizeBotHtml(html) {
    const tmp = document.createElement('div'); tmp.innerHTML = html;
    const walk = (node) => {
      for (const child of Array.from(node.childNodes)) {
        if (child.nodeType === Node.ELEMENT_NODE) {
          const tag = child.tagName.toLowerCase();
          if (tag === 'a') {
            const href = child.getAttribute('href') || '';
            if (!/^https?:\/\//i.test(href)) { child.replaceWith(document.createTextNode(child.textContent)); continue; }
            child.setAttribute('target','_blank'); child.setAttribute('rel','noopener noreferrer');
          } else if (tag !== 'br') {
            child.replaceWith(document.createTextNode(child.textContent));
          }
          walk(child);
        }
      }
    }; walk(tmp); return tmp.innerHTML;
  }
  function renderBotContent(s){ return /[<>]/.test(s) ? sanitizeBotHtml(s) : sanitizeBotHtml(linkifyText(s)); }

  // ---- Typing indicator min 2s
  const MIN_TYPING_MS = 2000; let typingStart = 0, typingTimer;
  const showTyping = () => { botTyping?.classList.remove('hidden'); typingStart = Date.now(); clearTimeout(typingTimer); };
  const hideTyping = () => { const remain = Math.max(0, MIN_TYPING_MS - (Date.now() - typingStart)); clearTimeout(typingTimer); typingTimer = setTimeout(()=>botTyping?.classList.add('hidden'), remain); };

  // ---- Counter
  function updateCounter(){
    let v = input.value || '';
    if (v.length > MAXLEN){ v = v.slice(0, MAXLEN); input.value = v; }
    counter.textContent = `${v.length}/${MAXLEN}`;
    sendBtn.disabled = sanitizeClientSide(v).length === 0;
    counter.classList.toggle('text-red-600', v.length >= MAXLEN);
  }
  input.addEventListener('input', updateCounter);

  // ---- Paste limit at caret
  input.addEventListener('paste', (e) => {
    const cd = e.clipboardData || window.clipboardData; if (!cd) return; e.preventDefault();
    const clip = cd.getData('text'); if (clip == null) return;
    const sanitized = String(clip).replace(INVISIBLE_RE, '');
    const start = input.selectionStart ?? input.value.length, end = input.selectionEnd ?? input.value.length;
    const before = input.value.slice(0, start), after = input.value.slice(end);
    const remaining = Math.max(0, MAXLEN - (before.length + after.length));
    const toInsert  = sanitized.slice(0, remaining);
    input.value = before + toInsert + after;
    const caret  = start + toInsert.length; input.setSelectionRange?.(caret, caret);
    updateCounter();
  });

  // ---- Enter to send
  input.addEventListener('keydown', (e) => {
    if (e.isComposing) return;
    if (e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); const raw = input.value; input.value = ''; updateCounter(); sendMessage(raw); }
  });

  // ---- Append bubbles (identical DOM to server-rendered)
  function appendUserBubble(text, time=''){
    messages.insertAdjacentHTML('beforeend', `
      <div class="msg-row flex flex-col w-full min-w-0 items-end text-right">
        <div class="bubble bubble-user px-4 py-2 rounded-2xl text-sm text-left"></div>
        <div class="msg-time text-[10px] text-gray-400 dark:text-gray-500 mt-1">${time}</div>
      </div>`);
    const row = messages.lastElementChild, bubble = row.querySelector('.bubble-user'), timeEl = row.querySelector('.msg-time');
    bubble.textContent = text;
    messages.scrollTop = messages.scrollHeight;
    return {row, timeEl};
  }
  function appendBotBubble(textOrHtml, time=''){
    messages.insertAdjacentHTML('beforeend', `
      <div class="msg-row flex flex-col w-full min-w-0 items-start">
        <div class="bubble bubble-ai px-4 py-2 rounded-2xl text-sm text-left"></div>
        <div class="msg-time text-[10px] text-gray-400 dark:text-gray-500 mt-1">${time}</div>
      </div>`);
    const bubble = messages.lastElementChild.querySelector('.bubble-ai');
    bubble.innerHTML = renderBotContent(textOrHtml);
    messages.scrollTop = messages.scrollHeight;
  }
  function appendWarnBubble(text){
    messages.insertAdjacentHTML('beforeend', `
      <div class="msg-row flex flex-col w-full min-w-0 items-start">
        <div class="inline-flex items-start gap-2 px-4 py-2 rounded-2xl text-sm bg-amber-50 text-amber-900 border border-amber-200">
          <span>⚠️</span><span>${text}</span>
        </div>
        <div class="msg-time text-[10px] text-gray-400 dark:text-gray-500 mt-1">{{ now()->format('H:i') }}</div>
      </div>`);
    messages.scrollTop = messages.scrollHeight;
  }

  // ---- Send
  async function sendMessage(text){
    const cleaned = sanitizeClientSide(text); if (!cleaned) return;
    if (/[<>]/.test(text)){ appendWarnBubble('HTML is not allowed in messages.'); return; }

    const {row, timeEl} = appendUserBubble(cleaned, '');
    sendBtn.disabled = true; showTyping();
    try{
      const idem = crypto.randomUUID ? crypto.randomUUID() : (Date.now() + '-' + Math.random().toString(16).slice(2));
      idemEl.value = idem;
      const res = await fetch(STORE_URL, {
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
        body: JSON.stringify({message: cleaned, _idem: idem})
      });
      if (!res.ok){ row.remove(); appendWarnBubble('No reply from LumiCHAT Assistant.'); return; }
      const data = await res.json();
      if (data?.user_message?.time_human && timeEl) timeEl.textContent = data.user_message.time_human;
      if (Array.isArray(data?.bot_reply) && data.bot_reply.length){
        data.bot_reply.forEach(r=>{
          const txt = typeof r === 'string' ? r : (r?.text ?? '');
          const t   = typeof r === 'object' ? (r?.time_human ?? '') : '';
          if (txt) appendBotBubble(txt, t);
        });
      } else appendWarnBubble('No reply from LumiCHAT Assistant.');
    } catch (e){ row.remove(); appendWarnBubble('No reply from LumiCHAT Assistant.'); console.error(e); }
    finally{ hideTyping(); sendBtn.disabled = false; input.focus(); updateCounter(); }
  }

  // ---- Submit
  form.addEventListener('submit', async (e)=>{ e.preventDefault(); const raw=input.value; input.value=''; updateCounter(); await sendMessage(raw); });

  // ---- Init
  updateCounter();
  if (messages) messages.scrollTop = messages.scrollHeight;
});
</script>
@endpush
