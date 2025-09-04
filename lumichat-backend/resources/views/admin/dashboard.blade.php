@extends('layouts.admin')

@section('title','Dashboard')

@section('content')
  <div class="space-y-6">

    {{-- Page intro --}}
    <div>
      <h2 class="text-3xl font-extrabold tracking-tight">Welcome back, Admin</h2>
      <p class="text-slate-600 mt-1">Here’s what’s happening with your students today.</p>
    </div>

    {{-- KPI / Stat cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

      {{-- Total Appointments --}}
      <div
        class="relative rounded-2xl border-[1px] p-5 bg-sky-50 border-sky-300 shadow-sm
               transition motion-safe:hover:-translate-y-0.5 hover:shadow-md
               hover:border-sky-400 focus-within:border-sky-500 focus-within:ring-2 focus-within:ring-sky-200">

        <details data-kpi class="group absolute right-2.5 top-2.5 z-50">
          <summary class="select-none list-none inline-flex items-center justify-center w-7 h-7 rounded-full
                          bg-white/80 ring-1 ring-sky-200 text-sky-700 hover:bg-white cursor-pointer
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300">
            <svg viewBox="0 0 24 24" class="w-[15px] h-[15px]" fill="currentColor" aria-hidden="true">
              <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm.75 15h-1.5v-6h1.5Zm0-7.5h-1.5v-1.5h1.5Z"/>
            </svg>
          </summary>
          <div class="select-none absolute right-0 top-9 w-64 rounded-md bg-white/95 text-slate-800
                      text-xs px-3 py-2 shadow-xl ring-1 ring-slate-200 backdrop-blur-sm
                      pointer-events-none opacity-0 translate-y-1 scale-95
                      transition duration-300 ease-out
                      group-open:opacity-100 group-open:translate-y-0 group-open:scale-100">
            <span class="absolute -top-1 right-3 h-3 w-3 rotate-45 bg-white ring-1 ring-slate-200"></span>
            <div class="font-semibold text-slate-700">Total Appointments</div>
            <p class="leading-snug text-slate-600 mt-0.5">
              All appointments recorded. Trend compares this week (Mon–Sun) vs last week.
            </p>
          </div>
        </details>

        <div class="relative z-10 flex items-start gap-4">
          <span class="shrink-0 inline-flex w-12 h-12 items-center justify-center rounded-xl bg-white/80 ring-1 ring-sky-200">
            <img src="{{ asset('images/icons/appointment.png') }}" class="w-6 h-6" alt="Appointments icon">
          </span>
          <div class="min-w-0">
            <div class="text-sm text-slate-600 font-medium">Total Appointments</div>
            <div class="mt-1 text-3xl font-bold text-slate-900" id="kpi-appointments">{{ number_format($appointmentsTotal ?? 0) }}</div>
            <div class="mt-0.5 text-xs text-slate-500" id="kpi-appointments-change">
              {{ $appointmentsTrend ?? '= Same as last week' }}
            </div>
          </div>
        </div>
      </div>

      {{-- Critical Cases (High-Risk Chatbot Sessions / Distinct users) --}}
      <div
        class="relative rounded-2xl border-[1px] p-5 bg-rose-50 border-rose-300 shadow-sm
               transition motion-safe:hover:-translate-y-0.5 hover:shadow-md
               hover:border-rose-400 focus-within:border-rose-500 focus-within:ring-2 focus-within:ring-rose-200">

        <details data-kpi class="group absolute right-2.5 top-2.5 z-50">
          <summary class="select-none list-none inline-flex items-center justify-center w-7 h-7 rounded-full
                          bg-white/80 ring-1 ring-rose-200 text-rose-700 hover:bg-white cursor-pointer
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300">
            <svg viewBox="0 0 24 24" class="w-[15px] h-[15px]" fill="currentColor" aria-hidden="true">
              <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm.75 15h-1.5v-6h1.5Zm0-7.5h-1.5v-1.5h1.5Z"/>
            </svg>
          </summary>
          <div class="select-none absolute right-0 top-9 w-72 rounded-md bg-white/95 text-slate-800
                      text-xs px-3 py-2 shadow-xl ring-1 ring-slate-200 backdrop-blur-sm
                      pointer-events-none opacity-0 translate-y-1 scale-95
                      transition duration-300 ease-out
                      group-open:opacity-100 group-open:translate-y-0 group-open:scale-100">
            <span class="absolute -top-1 right-3 h-3 w-3 rotate-45 bg-white ring-1 ring-slate-200"></span>
            <div class="font-semibold text-slate-700">Critical Cases</div>
            <p class="leading-snug text-slate-600 mt-0.5">
              Students with at least one <b>High-risk</b> chatbot session (distinct users). Requires attention.
            </p>
          </div>
        </details>

        <div class="relative z-10 flex items-start gap-4">
          <span class="shrink-0 inline-flex w-12 h-12 items-center justify-center rounded-xl bg-white/80 ring-1 ring-rose-200">
            <img src="{{ asset('images/icons/diagnosis.png') }}" class="w-6 h-6" alt="Critical cases icon">
          </span>
          <div class="min-w-0">
            <div class="text-sm text-slate-600 font-medium">Critical Cases</div>
            <div class="mt-1 text-3xl font-bold text-slate-900" id="kpi-critical-number">{{ number_format($criticalCasesTotal ?? 0) }}</div>
            <div class="mt-0.5 text-xs 
                {{ ($criticalCasesTotal ?? 0) > 0 ? 'text-rose-600 font-semibold' : 'text-slate-500' }}">
                Requires attention
            </div>
          </div>
        </div>
      </div>

      {{-- Active Counselor --}}
      <div
        class="relative rounded-2xl border-[1px] p-5 bg-amber-50 border-amber-300 shadow-sm
               transition motion-safe:hover:-translate-y-0.5 hover:shadow-md
               hover:border-amber-400 focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-200">

        <details data-kpi class="group absolute right-2.5 top-2.5 z-50">
          <summary class="select-none list-none inline-flex items-center justify-center w-7 h-7 rounded-full
                          bg-white/80 ring-1 ring-amber-200 text-amber-700 hover:bg-white cursor-pointer
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300">
            <svg viewBox="0 0 24 24" class="w-[15px] h-[15px]" fill="currentColor" aria-hidden="true">
              <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm.75 15h-1.5v-6h1.5Zm0-7.5h-1.5v-1.5h1.5Z"/>
            </svg>
          </summary>
          <div class="select-none absolute right-0 top-9 w-64 rounded-md bg-white/95 text-slate-800
                      text-xs px-3 py-2 shadow-xl ring-1 ring-slate-200 backdrop-blur-sm
                      pointer-events-none opacity-0 translate-y-1 scale-95
                      transition duration-300 ease-out
                      group-open:opacity-100 group-open:translate-y-0 group-open:scale-100">
            <span class="absolute -top-1 right-3 h-3 w-3 rotate-45 bg-white ring-1 ring-slate-200"></span>
            <div class="font-semibold text-slate-700">Active Counselor</div>
            <p class="leading-snug text-slate-600 mt-0.5">
              Number of counselors currently available to accept students.
            </p>
          </div>
        </details>

        <div class="relative z-10 flex items-start gap-4">
          <span class="shrink-0 inline-flex w-12 h-12 items-center justify-center rounded-xl bg-white/80 ring-1 ring-amber-200">
            <img src="{{ asset('images/icons/counselor.png') }}" class="w-6 h-6" alt="Counselor icon">
          </span>
          <div class="min-w-0">
            <div class="text-sm text-slate-600 font-medium">Active Counselor</div>
            <div class="mt-1 text-3xl font-bold text-slate-900" id="kpi-counselors">{{ number_format($activeCounselors ?? 0) }}</div>
            <div class="mt-0.5 text-xs text-slate-500">Available counselors</div>
          </div>
        </div>
      </div>

      {{-- Chat Sessions --}}
      <div
        class="relative rounded-2xl border-[1px] p-5 bg-indigo-50 border-indigo-300 shadow-sm
               transition motion-safe:hover:-translate-y-0.5 hover:shadow-md
               hover:border-indigo-400 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-200">

        <details data-kpi class="group absolute right-2.5 top-2.5 z-50">
          <summary class="select-none list-none inline-flex items-center justify-center w-7 h-7 rounded-full
                          bg-white/80 ring-1 ring-indigo-200 text-indigo-700 hover:bg-white cursor-pointer
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300">
            <svg viewBox="0 0 24 24" class="w-[15px] h-[15px]" fill="currentColor" aria-hidden="true">
              <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm.75 15h-1.5v-6h1.5Zm0-7.5h-1.5v-1.5h1.5Z"/>
            </svg>
          </summary>
          <div class="select-none absolute right-0 top-9 w-72 rounded-md bg-white/95 text-slate-800
                      text-xs px-3 py-2 shadow-xl ring-1 ring-slate-200 backdrop-blur-sm
                      pointer-events-none opacity-0 translate-y-1 scale-95
                      transition duration-300 ease-out
                      group-open:opacity-100 group-open:translate-y-0 group-open:scale-100">
            <span class="absolute -top-1 right-3 h-3 w-3 rotate-45 bg-white ring-1 ring-slate-200"></span>
            <div class="font-semibold text-slate-700">Chat Sessions</div>
            <p class="leading-snug text-slate-600 mt-0.5">
              Number of chatbot conversations started this week. Trend compares against last week.
            </p>
          </div>
        </details>

        <div class="relative z-10 flex items-start gap-4">
          <span class="shrink-0 inline-flex w-12 h-12 items-center justify-center rounded-xl bg-white/80 ring-1 ring-indigo-200">
            <img src="{{ asset('images/icons/chatbot-session.png') }}" class="w-6 h-6" alt="Chat sessions icon">
          </span>
          <div class="min-w-0">
            <div class="text-sm text-slate-600 font-medium">Chat Sessions</div>
            <div class="mt-1 text-3xl font-bold text-slate-900" id="kpi-sessions">{{ number_format($chatSessionsThisWeek ?? 0) }}</div>
            <div class="mt-0.5 text-xs text-slate-500" id="kpi-sessions-change">
              {{ $sessionsTrend ?? '= Same as last week' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Two-up content --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      {{-- Recent appointments --}}
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-md transition-shadow hover:shadow-lg">
        <div class="flex items-baseline justify-between mb-3">
          <h3 class="font-semibold">Recent Appointments</h3>
          <a class="text-sm text-indigo-600 hover:text-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 rounded"
             href="{{ route('admin.appointments.index') }}">View all</a>
        </div>

        @if(($recentAppointments ?? collect())->isEmpty())
          <p class="text-sm text-slate-500">No appointments yet.</p>
        @else
          <ul class="divide-y divide-slate-100 text-sm" id="list-recent-appointments">
            @foreach($recentAppointments as $appt)
              @php
                $status = strtolower($appt->status ?? 'scheduled');
                $dotClass =
                    (str_contains($status, 'cancel')   ? 'bg-rose-500'    :
                    (str_contains($status, 'confirm')  ? 'bg-emerald-500' :
                    (str_contains($status, 'complete') ? 'bg-indigo-500'  :
                    (str_contains($status, 'pending')  ? 'bg-amber-500'   :
                    ((str_contains($status,'critical') || str_contains($status,'urgent')) ? 'bg-rose-500' : 'bg-sky-500')))));
              @endphp
              <li class="py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                  <div>
                    <div class="font-medium">
                      {{ $appt->status ? ucfirst($appt->status) : 'Scheduled' }}
                    </div>
                    @if(!empty($appt->notes))
                      <div class="text-xs text-slate-500 line-clamp-1">{{ $appt->notes }}</div>
                    @endif
                  </div>
                </div>
                <span class="text-slate-400">
                  {{ optional($appt->when)->diffForHumans() }}
                </span>
              </li>
            @endforeach
          </ul>
        @endif
      </div>

      {{-- System Activity --}}
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-md transition-shadow hover:shadow-lg">
        <div class="flex items-baseline justify-between mb-3">
          <h3 class="font-semibold">System Activity</h3>
        </div>

        @if(($activities ?? collect())->isEmpty())
          <p class="text-sm text-slate-500">No activity yet.</p>
        @else
          <ul class="space-y-3 text-sm" id="list-activities">
            @foreach($activities as $a)
              @php
                $dot = str_starts_with($a->event, 'chat_session')
                        ? 'bg-indigo-500'
                        : (str_starts_with($a->event, 'user.registered') ? 'bg-emerald-500' : 'bg-slate-400');

                $text = str_starts_with($a->event, 'chat_session')
                          ? 'Chat session started: ' . ($a->meta ?? 'Starting conversation…')
                          : (str_starts_with($a->event, 'user.registered')
                              ? 'New user registered'
                              : 'Activity');
              @endphp

              <li class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="w-2 h-2 rounded-full {{ $dot }}"></span>
                  <span>
                    <span>{{ $text }}</span>
                    @if(!empty($a->actor))
                      <span class="text-slate-400 ml-2 text-xs">{{ $a->actor }}</span>
                    @endif
                  </span>
                </div>
                <span class="text-slate-400">{{ optional($a->created_at)->diffForHumans() }}</span>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>

    {{-- Recent chats --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-md transition-shadow hover:shadow-lg">
      <div class="flex items-baseline justify-between mb-3">
        <h3 class="font-semibold">Recent Chat Sessions</h3>
        <a class="text-sm text-indigo-600 hover:text-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 rounded"
           href="{{ route('admin.chatbot-sessions.index') }}">Open history</a>
      </div>

      @if(($recentChatSessions ?? collect())->isEmpty())
        <p class="text-sm text-slate-500">No chat sessions yet.</p>
      @else
        <ul class="divide-y divide-slate-100" id="list-recent-chats">
          @foreach($recentChatSessions as $s)
            @php
              $risk = strtolower($s->risk_level ?? 'low');
              $dotClass =
                  ($risk === 'high'      ? 'bg-rose-500' :
                  ($risk === 'moderate' ? 'bg-amber-500' :
                  'bg-indigo-500')); // default for low/unknown
            @endphp
            <li class="py-3 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                <div>
                  <span class="font-medium">
                    {{ $s->topic_summary ?: 'Starting conversation…' }}
                  </span>
                  @if(!empty($s->actor))
                    <span class="text-xs text-slate-400 ml-2">{{ $s->actor }}</span>
                  @endif
                </div>
              </div>
              <span class="text-slate-400 text-sm">{{ optional($s->created_at)->diffForHumans() }}</span>
            </li>
          @endforeach
        </ul>
      @endif
    </div>

  </div>
@endsection

@push('styles')
<style>
  /* Hide default marker on details/summary */
  details > summary::-webkit-details-marker { display: none; }
</style>
@endpush

@push('scripts')
<script>
/* ---------- Dashboard live refresh ---------- */
(function(){
  const endpoint = "{{ route('admin.dashboard.stats') }}";
  const nf  = new Intl.NumberFormat();
  const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

  function timeAgo(iso){
    if (!iso) return '';
    const thenMs = new Date(iso).getTime();
    const diffSec = (thenMs - Date.now()) / 1000;
    const roundTZ = (n) => (n < 0 ? Math.ceil(n) : Math.floor(n));
    const units = [
      ['year',31536000],['month',2592000],['week',604800],
      ['day',86400],['hour',3600],['minute',60],['second',1],
    ];
    for (const [u, s] of units) {
      const v = diffSec / s;
      if (Math.abs(v) >= 1 || u === 'second') return rtf.format(roundTZ(v), u);
    }
  }

  const esc = (s) => (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));

  function statusDot(status) {
    const s = (status || '').toLowerCase();
    if (s.includes('cancel'))   return 'bg-rose-500';
    if (s.includes('confirm'))  return 'bg-emerald-500';
    if (s.includes('complete')) return 'bg-indigo-500';
    if (s.includes('pending'))  return 'bg-amber-500';
    if (s.includes('critical') || s.includes('urgent')) return 'bg-rose-500';
    return 'bg-sky-500';
  }

  function liRecentAppointment(item){
    const status = (item.status || 'scheduled').toLowerCase();
    const dot = statusDot(status);
    const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Scheduled';
    return `
      <li class="py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <span class="w-2 h-2 rounded-full ${dot}"></span>
          <div>
            <div class="font-medium">${ esc(label) }</div>
            ${ item.notes ? `<div class="text-xs text-slate-500 line-clamp-1">${ esc(item.notes) }</div>` : '' }
          </div>
        </div>
        <span class="text-slate-400">${ esc(timeAgo(item.when) || '') }</span>
      </li>`;
  }

  function liActivity(a){
    const isChat = (a.event || '').startsWith('chat_session');
    const dot = isChat ? 'bg-indigo-500'
                       : ((a.event || '').startsWith('user.registered') ? 'bg-emerald-500' : 'bg-slate-400');
    const text = isChat ? `Chat session started: ${ esc(a.meta || 'Starting conversation…') }`
                        : ((a.event || '').startsWith('appointment') ? `Appointment: ${ esc(a.meta || '') }`
                        : ((a.event || '').startsWith('user.registered') ? 'New user registered' : 'Activity'));
    const actor = a.actor ? `<span class="text-slate-400 ml-2 text-xs">${ esc(a.actor) }</span>` : '';
    return `
      <li class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <span class="w-2 h-2 rounded-full ${dot}"></span>
          <span>${text} ${actor}</span>
        </div>
        <span class="text-slate-400">${ esc(timeAgo(a.created_at) || '') }</span>
      </li>`;
  }

  // Server-rendered "Recent Chat Sessions" already has colored dots.
  // The live-refresh list remains neutral (indigo) unless you also send risk in JSON.

    function liRecentChat(s){
    const risk = (s.risk_level || 'low').toLowerCase();
    let dot = 'bg-indigo-500'; // default low
    if (risk === 'high') dot = 'bg-rose-500';
    else if (risk === 'moderate') dot = 'bg-amber-500';

    return `
      <li class="py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <span class="w-2 h-2 rounded-full ${dot}"></span>
          <div>
            <span class="font-medium">${ esc(s.topic_summary || 'Starting conversation…') }</span>
            ${ s.actor ? `<span class="text-xs text-slate-400 ml-2">${ esc(s.actor) }</span>` : '' }
          </div>
        </div>
        <span class="text-slate-400 text-sm">${ esc(timeAgo(s.created_at) || '') }</span>
      </li>`;
  }


  let inflight;
  async function refresh(){
    try{
      if (inflight) inflight.abort();
      inflight = new AbortController();

      const res = await fetch(`${endpoint}?t=${Date.now()}`, {
        headers:{'X-Requested-With':'XMLHttpRequest'},
        credentials:'same-origin',
        cache:'no-store',
        signal: inflight.signal
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();

      // KPIs
      const k = data.kpis || {};
      const setNum = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = nf.format(val ?? 0); };
      setNum('kpi-appointments',      k.appointmentsTotal);
      setNum('kpi-counselors',        k.activeCounselors);
      setNum('kpi-sessions',          k.chatSessionsThisWeek);
      setNum('kpi-critical-number',   k.criticalCasesTotal); // <-- unified key

      const setText = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text || '= Same as last week'; };
      setText('kpi-appointments-change', k.appointmentsTrend);
      setText('kpi-sessions-change',     k.sessionsTrend);

      // Lists
      const put = (id, html) => { const el = document.getElementById(id); if (el) el.innerHTML = html; };

      const appts = (data.recentAppointments || []);
      put('list-recent-appointments',
        appts.length ? appts.map(liRecentAppointment).join('') : `<li class="py-3 text-sm text-slate-500">No appointments yet.</li>`
      );

      const acts = (data.activities || []);
      put('list-activities',
        acts.length ? acts.map(liActivity).join('') : `<li class="py-3 text-sm text-slate-500">No activity yet.</li>`
      );

      const chats = (data.recentChatSessions || []);
      put('list-recent-chats',
        chats.length ? chats.map(liRecentChat).join('') : `<li class="py-3 text-sm text-slate-500">No chat sessions yet.</li>`
      );
    } catch (e){
      if (e.name !== 'AbortError') console.error('Dashboard refresh failed:', e);
    }
  }

  setTimeout(refresh, 600);
  setInterval(refresh, 5000);
})();
</script>
@endpush
