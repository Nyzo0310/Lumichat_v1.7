@extends('layouts.app')
@section('title', 'Chat')


@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="chat-container relative">
  {{-- Chat Panel --}}
  <div id="chat-wrapper"
       class="chat-panel card-shell rounded-xl overflow-hidden animate__animated animate__slideInRight
              flex flex-col w-full max-w-[1040px] h-[calc(100vh-160px)]">

    <div class="chat-header flex items-center gap-3 bg-gradient-to-r from-indigo-600 to-purple-600
                text-white px-5 py-3 rounded-t-xl shadow">
      <img src="{{ asset('images/chatbot.png') }}" class="w-6 h-6" alt="Bot">
      <strong class="text-lg">LumiCHAT Assistant</strong>
    </div>

    <div id="chat-messages"
         class="flex-1 min-h-0 flex flex-col gap-3 p-4 overflow-y-auto bg-gray-50 dark:bg-gray-900">
      @foreach ($chats as $chat)
        @php($mine = $chat->sender !== 'bot')
        <div class="{{ $mine ? 'self-end text-right' : 'self-start' }}">
          <div class="inline-block max-w-xs px-4 py-2 rounded-2xl text-sm
                      {{ $mine ? 'bubble-user animate__animated animate__zoomIn'
                               : 'bubble-ai animate__animated animate__fadeIn' }}">
            {{-- Sanitize/escape on render for BOTH sides --}}
            {{ $chat->message }}
          </div>
          <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
            {{ \Carbon\Carbon::parse($chat->sent_at)->format('H:i') }}
          </div>
        </div>
      @endforeach
    </div>

    <form id="chat-form" class="flex items-center gap-2 px-4 py-3 border-t bg-white dark:bg-gray-800 dark:border-gray-700">
      @csrf
      <input type="hidden" id="idem" name="_idem" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
      <div class="group flex-1 flex items-center rounded-full bg-white dark:bg-gray-800
                  ring-1 ring-indigo-200 dark:ring-gray-700 focus-within:ring-2 focus-within:ring-indigo-400
                  transition shadow-sm">
        {{-- Use a textarea for long messages; enforce 2,000 max --}}
        <textarea id="chat-message" name="message" maxlength="2000" rows="1"
  enterkeyhint="send"
  class="flex-1 px-4 py-2 rounded-l-full input-dynamic !bg-transparent !border-0
         placeholder:text-gray-400 dark:placeholder-gray-500 resize-none"
  placeholder="Type your message..." autocomplete="off" required></textarea>

        <div class="text-[11px] text-gray-400 mr-2" id="char-counter">0/2000</div>

       <button id="sendBtn" disabled
  class="btn-primary m-1 rounded-full px-5 py-2 disabled:opacity-50 disabled:pointer-events-none">
  Send
</button>

      </div>
    </form>
  </div>
</div>

<p class="chat-footer-note text-center text-gray-400 dark:text-gray-500 text-xs mt-4">
  Your conversations are encrypted and private.
</p>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
  const messages = document.getElementById('chat-messages');
  const form     = document.getElementById('chat-form');
  const input    = document.getElementById('chat-message');
  const counter  = document.getElementById('char-counter');
  const sendBtn  = document.getElementById('sendBtn');
  const idemEl   = document.getElementById('idem');

  const STORE_URL = @json(route('chat.store'));

  const INVISIBLE_RE = /[\u200B\u200C\u200D\u2060\uFEFF]/g; // zero-width, word-joiner, no-break
  const URL_RE = /(https?:\/\/[^\s<>"']+)/gi;

  function scrollToBottom() {
    messages.scrollTop = messages.scrollHeight;
  }

  function sanitizeClientSide(raw) {
    let s = (raw || '').replace(INVISIBLE_RE, '');
    s = s.replace(/\s+/g, ' ').trim();
    return s;
  }

  function linkifyText(text) {
    return String(text).replace(URL_RE, (m) => {
      const href = m;
      return `<a href="${href}" target="_blank" rel="noopener noreferrer">${href}</a>`;
    });
  }

  function sanitizeBotHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;

    const walk = (node) => {
      for (const child of Array.from(node.childNodes)) {
        if (child.nodeType === Node.ELEMENT_NODE) {
          const tag = child.tagName.toLowerCase();
          if (tag === 'a') {
            const href = child.getAttribute('href') || '';
            if (!/^https?:\/\//i.test(href)) {
              child.replaceWith(document.createTextNode(child.textContent));
              continue;
            }
            child.setAttribute('target', '_blank');
            child.setAttribute('rel', 'noopener noreferrer');
            for (const attr of Array.from(child.attributes)) {
              const n = attr.name.toLowerCase();
              if (!['href','target','rel','class'].includes(n)) child.removeAttribute(attr.name);
            }
            walk(child);
          } else if (tag === 'br') {
            continue;
          } else {
            const textNode = document.createTextNode(child.textContent);
            child.replaceWith(textNode);
          }
        } else if (child.nodeType === Node.DOCUMENT_TYPE_NODE || child.nodeType === Node.COMMENT_NODE) {
          child.remove();
        }
      }
    };

    walk(tmp);
    return tmp.innerHTML;
  }

  function renderBotContent(textOrHtml) {
    if (/[<>]/.test(textOrHtml)) return sanitizeBotHtml(textOrHtml);
    return sanitizeBotHtml(linkifyText(textOrHtml));
  }

  function updateCounter() {
  const val = input.value || '';
  const len = val.length;

  counter.textContent = `${len}/2000`;
  sendBtn.disabled = sanitizeClientSide(val).length === 0;

  // minimal “limit reached” cue
  counter.classList.toggle('text-red-600', len >= 2000);
  input.title = len >= 2000 ? '2,000 character limit reached' : '';
}

  function appendUserBubble(text, time = '') {
    messages.insertAdjacentHTML('beforeend', `
      <div class="self-end text-right animate__animated animate__zoomIn chat-user-wrap">
        <div class="inline-block bubble-user px-4 py-2 rounded-2xl max-w-xs"></div>
        <div class="msg-time text-[10px] text-gray-400 dark:text-gray-500 mt-1">${time}</div>
      </div>
    `);
    const wrap   = messages.lastElementChild;
    const timeEl = wrap.querySelector('.msg-time');
    wrap.querySelector('.bubble-user').textContent = text;
    scrollToBottom();
    return { wrap, timeEl };
  }

  function appendBotBubble(textOrHtml, time = '') {
    messages.insertAdjacentHTML('beforeend', `
      <div class="self-start animate__animated animate__fadeIn">
        <div class="inline-block bubble-ai px-4 py-2 rounded-2xl max-w-xs"></div>
        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">${time}</div>
      </div>
    `);
    const el = messages.lastElementChild.querySelector('.bubble-ai');
    el.innerHTML = renderBotContent(textOrHtml);
    scrollToBottom();
  }

  function linkifyExistingBotBubbles() {
    document.querySelectorAll('#chat-messages .bubble-ai').forEach(el => {
      const txt = el.textContent || '';
      el.innerHTML = renderBotContent(txt);
    });
  }

  function appendWarnBubble(text) {
    messages.insertAdjacentHTML('beforeend', `
      <div class="self-start">
        <div class="inline-flex items-start gap-2 px-4 py-2 rounded-2xl text-sm bg-amber-50 text-amber-900 border border-amber-200">
          <span>⚠️</span><span>${text}</span>
        </div>
        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">{{ now()->format('H:i') }}</div>
      </div>
    `);
    scrollToBottom();
  }

  function showValidationErrors(data) {
    let msg = data?.message || 'Validation failed.';
    const firstField = data?.errors && Object.keys(data.errors)[0];
    if (firstField) {
      const firstError = data.errors[firstField]?.[0];
      if (firstError) msg = firstError;
    }
    appendWarnBubble(msg);
  }

  async function sendMessage(text) {
  
    const cleaned = sanitizeClientSide(text);
    if (!cleaned) return;

    // quick client guard for obvious HTML in user input
    if (/[<>]/.test(text)) {
      appendWarnBubble('HTML is not allowed in messages.');
      return;
    }

    const { wrap, timeEl } = appendUserBubble(cleaned, '');
    sendBtn.disabled = true;

    try {
      const idem = crypto.randomUUID ? crypto.randomUUID() : (Date.now()+'-'+Math.random().toString(16).slice(2));
      idemEl.value = idem;

      const res = await fetch(STORE_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ message: cleaned, _idem: idem })
      });

      if (res.status === 429) {
        wrap.remove();
        const retry = res.headers.get('Retry-After');
        appendWarnBubble(`Too many attempts. ${retry ? `Try again in ${retry} seconds.` : ''}`);
        return;
      }

      // Non-OK (e.g., 422 validation): remove optimistic bubble and show real error
      if (!res.ok) {
        let data = null;
        try { data = await res.json(); } catch (_) {}
        wrap.remove();
        if (res.status === 422 && data) {
          showValidationErrors(data);
        } else {
          appendWarnBubble('No reply from LumiCHAT Assistant.');
          if (data?.message) console.error('POST /chat failed', res.status, data.message);
        }
        return;
      }

      const ct = res.headers.get('content-type') || '';
      if (!ct.includes('application/json')) {
        wrap.remove();
        appendWarnBubble('No reply from LumiCHAT Assistant.');
        console.error('Non-JSON response from /chat:', await res.text());
        return;
      }

      const data = await res.json();
      if (data?.user_message?.time_human && timeEl) {
        timeEl.textContent = data.user_message.time_human;
      }

      if (Array.isArray(data?.bot_reply) && data.bot_reply.length > 0) {
        for (const r of data.bot_reply) {
          const txt = typeof r === 'string' ? r : (r?.text ?? '');
          const t   = typeof r === 'object' ? (r?.time_human ?? '') : '';
          if (txt) appendBotBubble(txt, t);
        }
      } else {
        appendWarnBubble('No reply from LumiCHAT Assistant.');
      }
    } catch (err) {
      wrap.remove();
      appendWarnBubble('No reply from LumiCHAT Assistant.');
      console.error('POST /chat failed', err);
    } finally {
      sendBtn.disabled = false;
      input.focus();
      updateCounter();
    }
  }

  // Enter to send (Shift+Enter inserts newline; IME-safe)
  input.addEventListener('keydown', (e) => {
    if (e.isComposing) return;
    if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.metaKey && !e.altKey) {
      e.preventDefault();
      const raw = input.value;
      const cleaned = sanitizeClientSide(raw);
      if (!cleaned) return;
      input.value = '';
      updateCounter();
      sendMessage(cleaned);
    }
  });

  // Robust paste (sanitize, enforce maxlength, insert at caret)
  input.addEventListener('paste', (e) => {
    const cd = e.clipboardData || window.clipboardData;
    if (!cd) return;
    e.preventDefault();

    const clip = cd.getData('text');
    if (clip == null) return;

    const sanitized = String(clip).replace(INVISIBLE_RE, '');

    const start = input.selectionStart ?? input.value.length;
    const end   = input.selectionEnd   ?? input.value.length;
    const before = input.value.slice(0, start);
    const after  = input.value.slice(end);

    const max = parseInt(input.getAttribute('maxlength') || '0', 10) || Infinity;
    const remaining = Math.max(0, max - (before.length + after.length));
    const toInsert = sanitized.slice(0, remaining);

    input.value = before + toInsert + after;

    const caret = start + toInsert.length;
    input.setSelectionRange?.(caret, caret);

    updateCounter();
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const raw = input.value;
    input.value = '';
    updateCounter();
    await sendMessage(raw);
  });

  input.addEventListener('input', updateCounter);
  updateCounter();
  linkifyExistingBotBubbles();
  scrollToBottom();
});
</script>
@endpush