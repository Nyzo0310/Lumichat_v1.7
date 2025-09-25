@extends('layouts.app')
@section('title', 'Chat')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* --- typing dots animation (kept your styles) --- */
@keyframes bounceDots{0%,80%,100%{transform:translateY(0);opacity:.6}40%{transform:translateY(-4px);opacity:1}}
#bot-typing .dot{display:inline-block;animation:bounceDots 1.3s infinite}
#bot-typing .dot:nth-child(2){animation-delay:.2s}
#bot-typing .dot:nth-child(3){animation-delay:.4s}

/* Quick replies (kept) */
.bot-actions{margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap}
.bot-actions .qr-btn{font-size:.75rem;padding:.375rem .75rem;border-radius:.75rem;border:1px solid rgba(99,102,241,.35);background:rgba(99,102,241,.06)}
.bot-actions .qr-btn[data-variant="primary"]{background:#4f46e5;color:#fff;border-color:#4f46e5}
.bot-actions .qr-btn:hover{background:rgba(99,102,241,.12)}

/* header typing moved into bubbles */
#bot-typing{display:none!important}

/* make in-bubble dots readable without changing theme */
.bubble-ai{ color:#6b7280; } /* neutral gray; dots inherit currentColor */
</style>

<div class="px-4 sm:px-6">
  <div class="mx-auto w-full max-w-5xl h-[80vh]">

    {{-- ===================== Chat Panel ===================== --}}
  <div id="chat-wrapper"
     class="chat-panel card-shell rounded-2xl overflow-hidden flex flex-col w-full chat-appear"
     style="height:80vh"
     data-thread-id="{{ $thread->id ?? ('draft-'.\Illuminate\Support\Str::uuid()) }}">

      {{-- ===================== Header ===================== --}}
      <div class="chat-header flex items-center gap-3 bg-gradient-to-r from-indigo-600 to-purple-600
                  text-white px-5 py-3 shadow">
        <img src="{{ asset('images/chatbot.png') }}" class="w-6 h-6" alt="Bot">
        <div class="min-w-0">
          <strong class="text-lg leading-tight">LumiCHAT Assistant</strong>
          <div class="text-xs text-white/80 hidden sm:block">Friendly support that respects your privacy</div>
        </div>

        {{-- legacy header typing (hidden; dots now appear inside chat bubble) --}}
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
            <div class="bubble {{ $mine ? 'bubble-user' : 'bubble-ai' }} px-4 py-2 rounded-2xl text-base text-left">
              {{ $chat->message }}
            </div>
            <div class="msg-time text-[10px] text-gray-400 dark:text-gray-500 mt-1">
              {{ \Carbon\Carbon::parse($chat->sent_at ?? $chat->created_at)->format('H:i') }}
            </div>
          </div>
        @endforeach
      </div>

      {{-- ===================== Composer ===================== --}}
      <form id="chat-form" action="{{ route('chat.store') }}" method="POST"
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
                   disabled:opacity-50 disabled:pointer-events-none" type="submit">
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
  {{-- LumiCHAT scripts (module + nomodule fallback) --}}
  <script type="module" src="/build/assets/chat.js?v=2025-09-23-1"></script>
  <script nomodule src="/chat.js?v=2025-09-23-1"></script>

  <script>
  /* ===== Inline fallback (full features). 
     It will ONLY run if an external chat.js is not already active. ===== */
  (function(){
    if (window.LUMI_CHAT_JS_ACTIVE) return;   // if external file already bound, skip
    window.LUMI_CHAT_JS_ACTIVE = true;

    document.addEventListener('DOMContentLoaded', () => {
      const messages = document.getElementById('chat-messages');
      const form     = document.getElementById('chat-form');
      const input    = document.getElementById('chat-message');
      const counter  = document.getElementById('char-counter');
      const sendBtn  = document.getElementById('sendBtn');
      const idemEl   = document.getElementById('idem');
      const chatWrapper = document.getElementById('chat-wrapper');

      const STORE_URL = @json(route('chat.store'));
      const MAXLEN = 2000;

      // ---- Panel exit (kept)
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
      const sanitizeClient = raw => (raw || '').replace(INVISIBLE_RE,'').replace(/\s+/g,' ').trim();
      const linkify = t => String(t).replace(URL_RE, m => `<a href="${m}" target="_blank" rel="noopener noreferrer">${m}</a>`);

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
        };
        walk(tmp); 
        return tmp.innerHTML;
      }
      const renderBotContent = s => /[<>]/.test(s) ? sanitizeBotHtml(s) : sanitizeBotHtml(linkify(s));

      // ---- Counter
      function updateCounter(){
        let v = input.value || '';
        if (v.length > MAXLEN){ v = v.slice(0, MAXLEN); input.value = v; }
        counter.textContent = `${v.length}/${MAXLEN}`;
        sendBtn.disabled = sanitizeClient(v).length === 0;
        counter.classList.toggle('text-red-600', v.length >= MAXLEN);
      }
      input.addEventListener('input', updateCounter);
      input.addEventListener('paste', (e)=>{
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

      // ---- Append bubbles (match server DOM)
      function appendUserBubble(text, time=''){
        messages.insertAdjacentHTML('beforeend', `
          <div class="msg-row flex flex-col w-full min-w-0 items-end text-right">
            <div class="bubble bubble-user px-4 py-2 rounded-2xl text-base text-left"></div>
            <div class="msg-time text-[10px] text-gray-400 dark:text-gray-500 mt-1">${time}</div>
          </div>`);
        const row = messages.lastElementChild, bubble = row.querySelector('.bubble-user'), timeEl = row.querySelector('.msg-time');
        bubble.textContent = text;
        messages.scrollTop = messages.scrollHeight;
        return {row, timeEl};
      }
      function appendBotBubbleShell(time=''){
        messages.insertAdjacentHTML('beforeend', `
          <div class="msg-row flex flex-col w-full min-w-0 items-start">
            <div class="bubble bubble-ai px-4 py-2 rounded-2xl text-base text-left"></div>
            <div class="msg-time text-[10px] text-gray-400 dark:text-gray-500 mt-1">${time}</div>
          </div>`);
        return messages.lastElementChild.querySelector('.bubble-ai');
      }

      // ---- Dots + typewriter + queue
      function typewriter(bubble, finalHTML, speed=25, minDotsMs=900){
        const reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        return new Promise((resolve)=>{
          if (reduced){ bubble.innerHTML = finalHTML; resolve(); return; }
          const start = performance.now();
          const waitDots = () => {
            if (performance.now() - start < minDotsMs) return requestAnimationFrame(waitDots);
            const tmp = document.createElement('div'); tmp.innerHTML = finalHTML;
            const plain = tmp.textContent || tmp.innerText || '';
            bubble.textContent = '';
            let i = 0;
            (function tick(){
              bubble.textContent = plain.slice(0, i+1);
              i++; messages.scrollTop = messages.scrollHeight;
              if (i < plain.length) setTimeout(tick, speed);
              else { bubble.innerHTML = finalHTML; messages.scrollTop = messages.scrollHeight; resolve(); }
            })();
          };
          requestAnimationFrame(waitDots);
        });
      }

      async function appendBotBubble(payload, time=''){
        const bubble = appendBotBubbleShell(time);
        // dots inside bubble
        bubble.innerHTML = `
          <span class="inline-flex items-center gap-1">
            <span class="dot w-2 h-2 rounded-full"></span>
            <span class="dot w-2 h-2 rounded-full"></span>
            <span class="dot w-2 h-2 rounded-full"></span>
          </span>
          <span class="sr-only">Assistant is typing…</span>`;
        messages.scrollTop = messages.scrollHeight;

        // natural think time
        await new Promise(r => setTimeout(r, 320 + Math.floor(Math.random()*420)));

        // final text (safe)
        const text = (typeof payload === 'object' && payload) ? (payload.text ?? payload.bot_reply ?? payload.message ?? '') : payload;
        const html = renderBotContent(text || '');
        await typewriter(bubble, html, 24, 650);

        // (optional) domain quick actions – based on message content
        try{
          const raw = (bubble.textContent || '').toLowerCase();
          const isCoping   = /share\s+coping\s+tips/.test(bubble.textContent) || (/coping\s+mechanism/.test(raw) && /want(\s+them)?\s+now\??/.test(raw));
          const isReferral = /open the appointment page\??/i.test(bubble.textContent) || /book\s+counselor/.test(raw);
          if (isCoping || isReferral){
            const box = document.createElement('div');
            box.className = 'bot-actions';
            box.innerHTML = isCoping
              ? `<button class="qr-btn" data-qr='/deny{"confirm_topic":"coping"}'>No, thanks</button>
                 <button class="qr-btn" data-variant="primary" data-qr='/affirm{"confirm_topic":"coping"}'>Yes, show tips</button>`
              : `<a class="qr-btn" data-variant="primary" href="http://127.0.0.1:8000/appointment/book" rel="noopener">Book counselor</a>
                 <button class="qr-btn" data-qr='/deny{"confirm_topic":"referral"}'>Not now</button>`;
            box.addEventListener('click', (ev)=>{
              const btn = ev.target.closest('.qr-btn'); 
              if (!btn || btn.tagName === 'A') return;
              const payload = btn.getAttribute('data-qr') || btn.textContent.trim();
              appendUserBubble(payload, new Date().toLocaleTimeString());
              send(payload);
            });
            bubble.appendChild(box);
          }
        }catch{}

        return bubble;
      }

      // strict queue so replies never overlap
      let Q = Promise.resolve();
      const runQ = (task) => (Q = Q.then(task).catch(e => console.warn('[LumiCHAT] queue error', e)));

      function sendQuick(text){
        appendUserBubble(text, new Date().toLocaleTimeString());
        send(text);
      }

      async function send(message){
        try{
          sendBtn && (sendBtn.disabled = true);
          const idem = (window.crypto?.randomUUID?.() ?? (Date.now() + '-' + Math.random().toString(16).slice(2)));
          idemEl.value = idem;

          const res = await fetch(STORE_URL, {
            method:'POST',
            headers:{
              'Content-Type':'application/json',
              'Accept':'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ message, _idem: idem })
          });

          if (!res.ok){ await runQ(()=>appendBotBubble('No reply from LumiCHAT Assistant.', '')); return; }
          const data = await res.json();

          let replies = data?.bot_reply;
          if (!Array.isArray(replies)) replies = [replies];

          for (const r of (replies || [])){
            await runQ(() => appendBotBubble(r, data?.time_human || ''));
            await runQ(() => new Promise(done => setTimeout(done, 240))); // tiny post pause
          }
        } catch (e){
          console.error(e);
          await runQ(()=>appendBotBubble('No reply from LumiCHAT Assistant.', ''));
        } finally {
          sendBtn && (sendBtn.disabled = false);
          input && input.focus();
        }
      }

      // enter-to-send
      input.addEventListener('keydown', (e) => {
        if (e.isComposing) return;
        if (e.key === 'Enter' && !e.shiftKey){
          e.preventDefault();
          const raw = input.value;
          input.value = '';
          updateCounter();
          const cleaned = sanitizeClient(raw);
          if (!cleaned) return;
          appendUserBubble(cleaned, new Date().toLocaleTimeString());
          send(cleaned);
        }
      });

      // submit handler (button click)
      if (!form.dataset.bound){
        form.dataset.bound = '1';
        form.addEventListener('submit', (e)=>{
          e.preventDefault();
          const raw = input.value;
          input.value = '';
          updateCounter();
          const cleaned = sanitizeClient(raw);
          if (!cleaned) return;
          appendUserBubble(cleaned, new Date().toLocaleTimeString());
          send(cleaned);
        });
      }

      // ---- Init
      function updateCounter(){ /* defined earlier; reattached to keep scope */ }
      // Call the actual one now:
      (function(){ const evt=new Event('input'); input.dispatchEvent(evt); })();
      if (messages) messages.scrollTop = messages.scrollHeight;

      /* === Auto-welcome on first load (only if no messages) === */
      try {
        const hasMessages = !!messages.querySelector('.msg-row');
        if (!hasMessages && !sessionStorage.getItem('lumi_welcome')) {
          sessionStorage.setItem('lumi_welcome','1');
          runQ(() => appendBotBubble("Hi! I’m Lumi — how can I help you today?", ""));
        }
      } catch (e) { console.warn("Welcome message skipped:", e); }

      console.log('%c[LumiCHAT] inline chat script active', 'color:#4f46e5;font-weight:bold');
    });

  })();
  </script>
@endpush
