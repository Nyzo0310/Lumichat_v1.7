<div class="appt-compact">
  {{-- TOP-LEFT LOGO --}}
  <div class="logo-strip">
    <img src="{{ asset('images/chatbot.png') }}" alt="LumiCHAT" class="logo">
  </div>

  {{-- Centered header row: Student / Counselor / Date / Time --}}
  <div class="kv-center-wrap">
    <div class="kv-grid">
      <div><b>Student:</b> {{ $student }}</div>
      <div><b>Counselor:</b> {{ $counselor }}</div>
      <div><b>Date:</b> {{ $date }}</div>
      <div><b>Time:</b> {{ $time }}</div>
    </div>
  </div>

  <hr>

  <p><b>Note sent to student:</b></p>
  <div class="note">{!! nl2br(e($note)) !!}</div>
</div>
