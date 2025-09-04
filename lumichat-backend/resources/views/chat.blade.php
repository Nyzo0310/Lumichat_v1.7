@extends('layouts.app')
@section('title', 'Chat')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="chat-container relative">

  {{-- Greeting Overlay (first-time & when "New Chat" or after SA) --}}
  <div id="greeting-overlay"
       class="fixed inset-0 bg-gradient-to-br from-purple-600 via-violet-500 to-indigo-500
              flex flex-col items-center justify-center text-white z-50 px-4
              animate__animated animate__fadeIn {{ $showGreeting ? '' : 'hidden' }}">

    <img src="{{ asset('images/chatbot.png') }}" alt="Bot" class="w-16 h-16 mb-4 animate__animated animate__zoomIn">
    <h1 class="text-4xl font-extrabold leading-snug text-center mb-6 animate__animated animate__bounceInDown">
      Hey there!<br>How are you feeling today?
    </h1>

    <div class="flex flex-wrap justify-center gap-3 mb-6">
      @foreach(['Happy','Sad','Anxious','Stressed','Curious'] as $feel)
        <button
          class="px-5 py-2 bg-white/20 backdrop-blur-sm rounded-full hover:bg-white/40 transition font-medium animate__animated animate__pulse animate__infinite animate__slow"
          onclick="document.getElementById('greeting-input').value='I am feeling {{ strtolower($feel) }}'; document.getElementById('greeting-send').click()">
          {{ $feel }}
        </button>
      @endforeach
    </div>

    <form id="greeting-form" class="w-full max-w-md mx-auto" onsubmit="return false;">
      <div class="relative">
        <input id="greeting-input" type="text"
               placeholder="Type a feeling or question..."
               class="w-full py-4 pl-5 pr-28 rounded-full text-gray-900 text-base
                      shadow-lg focus:outline-none focus:ring-2 focus:ring-white/80
                      placeholder:text-gray-700 bg-white/90"/>
        <button id="greeting-send" type="button"
                class="absolute top-1/2 right-2 -translate-y-1/2
                       px-5 py-2 rounded-full text-white text-sm font-medium
                       bg-indigo-500 disabled:bg-indigo-400/60 hover:bg-indigo-600 transition shadow">
          Send
        </button>
      </div>

      <p class="text-sm opacity-90 mt-4 text-white/90 animate__animated animate__fadeInUp">
        We prioritize your mental health and privacy. Your chats are safe with us.
      </p>
    </form>
  </div>

  {{-- Chat Panel --}}
  <div id="chat-wrapper"
       class="chat-panel card-shell rounded-xl overflow-hidden {{ $showGreeting ? 'hidden' : '' }} animate__animated animate__slideInRight
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
  const overlay      = document.getElementById('greeting-overlay');
  const chatWrap     = document.getElementById('chat-wrapper');
  const greetingForm = document.getElementById('greeting-form');
  const greetingIn   = document.getElementById('greeting-input');
  const greetingBt   = document.getElementById('greeting-send');

  const messages = document.getElementById('chat-messages');
  const form     = document.getElementById('chat-form');
  const input    = document.getElementById('chat-message');

  const STORE_URL = @json(route('chat.store'));

  const toggleStartBtn = () => { greetingBt.disabled = !greetingIn.value.trim(); };
  greetingIn.addEventListener('input', toggleStartBtn); toggleStartBtn();

  function appendUserBubble(text, time = '') {
    messages.insertAdjacentHTML('beforeend', `
      <div class="self-end text-right animate__animated animate__zoomIn">
        <div class="inline-block bubble-user px-4 py-2 rounded-2xl max-w-xs"></div>
        <div class="msg-time text-[10px] text-gray-400 dark:text-gray-500 mt-1">${time}</div>
      </div>
    `);
    messages.lastElementChild.querySelector('.bubble-user').textContent = text;
    messages.scrollTop = messages.scrollHeight;
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
    messages.scrollTop = messages.scrollHeight;
  }

  async function sendMessage(text) {
    if (!text.trim()) return;
    const timeEl = appendUserBubble(text, '');

    let data;
    try {
      const res = await fetch(STORE_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ message: text })
      });
      data = await res.json();
    } catch (e) {
      console.error('POST /chat failed', e);
      return;
    }

    if (data?.user_message?.time_human && timeEl) {
      timeEl.textContent = data.user_message.time_human;
    }

    if (Array.isArray(data?.bot_reply)) {
      for (const r of data.bot_reply) {
        const botText = typeof r === 'string' ? r : (r?.text ?? '');
        const botTime = typeof r === 'object' ? (r?.time_human ?? '') : '';
        if (botText) appendBotBubble(botText, botTime);
      }
    }
  }

  async function startFromGreeting() {
    const txt = greetingIn.value.trim();
    if (!txt) return;

    overlay.classList.add('animate__fadeOut');
    setTimeout(() => overlay.classList.add('hidden'), 300);
    chatWrap.classList.remove('hidden');

    await sendMessage(txt);
    greetingIn.value = '';
    toggleStartBtn();
  }

  greetingBt.addEventListener('click', startFromGreeting);
  greetingForm.addEventListener('submit', (e) => { e.preventDefault(); startFromGreeting(); });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const txt = input.value.trim();
    if (!txt) return;
    input.value = '';
    await sendMessage(txt);
  });
});
</script>
@endpush
