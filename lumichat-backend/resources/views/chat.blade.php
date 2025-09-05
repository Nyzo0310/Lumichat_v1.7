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
            {!! $mine ? e($chat->message) : $chat->message !!}
          </div>
          <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
            {{ \Carbon\Carbon::parse($chat->sent_at)->format('H:i') }}
          </div>
        </div>
      @endforeach
    </div>

    <form id="chat-form" class="flex items-center gap-2 px-4 py-3 border-t bg-white dark:bg-gray-800 dark:border-gray-700">
      @csrf
      <div class="group flex-1 flex items-center rounded-full bg-white dark:bg-gray-800
                  ring-1 ring-indigo-200 dark:ring-gray-700 focus-within:ring-2 focus-within:ring-indigo-400
                  transition shadow-sm">
        <input id="chat-message" name="message"
               class="flex-1 px-4 py-2 rounded-l-full input-dynamic !bg-transparent !border-0
                      placeholder:text-gray-400 dark:placeholder-gray-500"
               placeholder="Type your message..." autocomplete="off" required>
        <button type="submit" class="btn-primary m-1 rounded-full px-5 py-2">
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

  const STORE_URL = @json(route('chat.store'));

  function scrollToBottom() {
    messages.scrollTop = messages.scrollHeight;
  }

  function appendUserBubble(text, time = '') {
    messages.insertAdjacentHTML('beforeend', `
      <div class="self-end text-right animate__animated animate__zoomIn">
        <div class="inline-block bubble-user px-4 py-2 rounded-2xl max-w-xs"></div>
        <div class="msg-time text-[10px] text-gray-400 dark:text-gray-500 mt-1">${time}</div>
      </div>
    `);
    messages.lastElementChild.querySelector('.bubble-user').textContent = text;
    scrollToBottom();
    return messages.lastElementChild.querySelector('.msg-time');
  }

  function appendBotBubble(html, time = '') {
    messages.insertAdjacentHTML('beforeend', `
      <div class="self-start animate__animated animate__fadeIn">
        <div class="inline-block bubble-ai px-4 py-2 rounded-2xl max-w-xs"></div>
        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">${time}</div>
      </div>
    `);
    messages.lastElementChild.querySelector('.bubble-ai').innerHTML = html;
    scrollToBottom();
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

  async function sendMessage(text) {
    if (!text.trim()) return;

    // show user bubble immediately
    const timeEl = appendUserBubble(text, '');

    try {
      const res = await fetch(STORE_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ message: text })
      });

      // If Laravel returns an HTML error page, avoid JSON.parse crash
      const ct = res.headers.get('content-type') || '';
      if (!ct.includes('application/json')) {
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
          const botText = typeof r === 'string' ? r : (r?.text ?? '');
          const botTime = typeof r === 'object' ? (r?.time_human ?? '') : '';
          if (botText) appendBotBubble(botText, botTime);
        }
      } else {
        appendWarnBubble('No reply from LumiCHAT Assistant.');
      }
    } catch (err) {
      appendWarnBubble('No reply from LumiCHAT Assistant.');
      console.error('POST /chat failed', err);
    }
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const txt = input.value.trim();
    if (!txt) return;
    input.value = '';
    await sendMessage(txt);
  });

  // Keep scrolled to bottom on load (useful when there’s history)
  scrollToBottom();
});
</script>
@endpush
