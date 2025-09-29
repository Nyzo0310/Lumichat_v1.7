@extends('layouts.admin')
@section('title','Chatbot Session')

@section('content')
@php
  $codeYear = $session->created_at?->format('Y') ?? now()->format('Y');
  $code     = 'LMC-' . $codeYear . '-' . str_pad($session->id, 4, '0', STR_PAD_LEFT);

  $isHighRisk = in_array(strtolower((string)($session->risk_level ?? $session->risk ?? '')), ['high','high-risk','high_risk'], true)
                || (int)($session->risk_score ?? 0) >= 80;

  // Book button only for high-risk *and* no active appointment for THIS session
  $canBook = $isHighRisk && empty($hasActiveForStudent);
@endphp

<div class="max-w-5xl mx-auto p-6 space-y-6">

  {{-- Header row --}}
  <div class="flex items-center justify-between no-print">
    <h2 class="text-2xl font-bold tracking-tight text-slate-800">Chatbot Session</h2>
    <div class="flex items-center gap-2">
      {{-- ADMIN BOOKING (High-Risk only and no active appt yet for this session) --}}
      @if($canBook)
        <button type="button" id="btnAdminBook"
          class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 2a1 1 0 0 0-1 1v1H5a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-1V3a1 1 0 1 0-2 0v1H8V3a1 1 0 0 0-1-1ZM5 9h14v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V9Z"/>
          </svg>
          Book (High-Risk)
        </button>
      @elseif($isHighRisk)
        <span class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-200 text-slate-700 cursor-not-allowed">
          Student already has an active appointment
        </span>
      @endif

      <button type="button"
              class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-slate-800 hover:bg-slate-50"
              onclick="printNode('#sessionPrintable', 'Chatbot Session {{ $code }}')">
        Print
      </button>
      <a href="{{ route('admin.chatbot-sessions.index') }}"
         class="text-sm text-indigo-600 hover:underline">← Back to list</a>
    </div>
  </div>

  {{-- PRINTABLE AREA --}}
  <div id="sessionPrintable" class="space-y-6 print-area">

    {{-- Summary card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <div class="text-xs text-slate-500 uppercase">Session ID</div>
          <div class="mt-1 font-semibold text-slate-900 flex items-center gap-2">
            <span id="sessionCode">{{ $code }}</span>
            <button type="button"
                    onclick="copyText('#sessionCode')"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 no-print"
                    title="Copy Session ID">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2" stroke-width="2"/>
                <rect x="3" y="3" width="13" height="13" rx="2" ry="2" stroke-width="2"/>
              </svg>
            </button>
          </div>
        </div>

        <div>
          <div class="text-xs text-slate-500 uppercase">Initial Result</div>
          <div class="mt-1 font-medium text-slate-800">
            {{ $session->topic_summary ?? '—' }}
          </div>
        </div>

        <div>
          <div class="text-xs text-slate-500 uppercase">Student</div>
          <div class="mt-1 font-medium text-slate-800">{{ $session->user->name ?? '—' }}</div>
        </div>

        <div>
          <div class="text-xs text-slate-500 uppercase">Initial Date</div>
          <div class="mt-1 font-medium text-slate-800">
            {{ $session->created_at?->format('F d, Y • h:i A') }}
          </div>
        </div>
      </div>
    </div>

    {{-- Session Counts (week) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 p-6">
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-slate-900">Session Counts</h3>

        <div class="flex items-center gap-2 no-print">
          <button id="calPrev"
                  class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm ring-1 ring-slate-200 hover:bg-slate-50">
            ← Prev
          </button>

          <button id="calToday"
                  class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm bg-indigo-600 text-white hover:bg-indigo-700">
            Today
          </button>

          <button id="calNext"
                  class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm ring-1 ring-slate-200 hover:bg-slate-50">
            Next →
          </button>
        </div>
      </div>

      <div id="calRange" class="mt-2 text-sm text-slate-500"></div>

      <div class="mt-4 overflow-hidden rounded-xl ring-1 ring-slate-200/70">
        <div class="grid grid-cols-7 bg-slate-50/60 text-xs font-medium uppercase tracking-wide text-slate-600">
          <div class="px-3 py-2 text-center">Sun</div>
          <div class="px-3 py-2 text-center">Mon</div>
          <div class="px-3 py-2 text-center">Tue</div>
          <div class="px-3 py-2 text-center">Wed</div>
          <div class="px-3 py-2 text-center">Thu</div>
          <div class="px-3 py-2 text-center">Fri</div>
          <div class="px-3 py-2 text-center">Sat</div>
        </div>

        <div class="grid grid-cols-7 divide-x divide-slate-200/70 text-center">
          @for ($i = 0; $i < 7; $i++)
            <div class="px-3 py-6">
              <div id="cnt{{ $i }}" class="text-xl font-semibold text-slate-900">—</div>
              <div class="mt-1 text-xs text-slate-500">sessions</div>
            </div>
          @endfor
        </div>
      </div>
    </div>
  </div> {{-- /#sessionPrintable --}}

  {{-- Footer actions --}}
  <div class="flex items-center justify-end gap-2 no-print">
    @if(!empty($session->user?->email))
      <a href="mailto:{{ $session->user->email }}"
         class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white text-slate-800 ring-1 ring-slate-200 hover:bg-slate-50">
        Email Student
      </a>
    @endif

    <button type="button"
            onclick="copyText('#sessionCode')"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white text-slate-800 ring-1 ring-slate-200 hover:bg-slate-50">
      Copy Session ID
    </button>
  </div>
</div>

{{-- Lightweight modal styles to increase weight/contrast --}}
<style>
  .pill {
    font-weight: 600;
    border-radius: 0.75rem;
    padding: .7rem .9rem;
    line-height: 1.1;
    box-shadow: 0 1px 0 0 rgba(2,6,23,.06), 0 0 0 1px rgba(15,23,42,.06) inset;
  }
  .pill .time {
    display:block; font-size:.95rem;
  }
  .pill .cap {
    display:block; font-size:.72rem; opacity:.8; margin-top:.15rem;
  }
  .pill[disabled] { opacity:.5; cursor:not-allowed; }
  .pill--active { outline: 3px solid rgba(79,70,229,.8); }
  .pill:hover:not([disabled]) {
    background: #EEF2FF;
    border-color: #C7D2FE;
  }
  .grid-times {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  @media (min-width: 640px){ .grid-times{ grid-template-columns: repeat(4, minmax(0, 1fr)); } }
</style>

<script>
  function copyText(selector){
    const el = document.querySelector(selector);
    if(!el) return;
    const text = el.textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
      if (window.Swal) {
        Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Copied', showConfirmButton:false, timer:1400 });
      }
    });
  }
</script>

@push('scripts')
<script>
function printNode(selector, title = document.title) {
  const node = document.querySelector(selector) || document.body;
  const w = window.open('', '_blank', 'width=1024,height=700');
  const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style')).map(n => n.outerHTML).join('\n');
  w.document.write(`
    <html><head><meta charset="utf-8"><title>${title}</title>${styles}
      <style>@page{margin:1.2cm}@media print{.no-print{display:none!important}body{background:#fff!important}.print-area{box-shadow:none!important}}</style>
    </head><body>${node.outerHTML}</body></html>
  `);
  w.document.close(); w.focus(); w.onload = () => w.print();
}
</script>

<script>
(() => {
  const endpoint = @json(route('admin.chatbot-sessions.calendar', $session->id));
  const rangeEl = document.getElementById('calRange');
  const prevBtn = document.getElementById('calPrev');
  const nextBtn = document.getElementById('calNext');
  const todayBtn = document.getElementById('calToday');
  const cntEls = [...Array(7)].map((_, i) => document.getElementById('cnt' + i));
  let anchor = new Date();

  const pad = n => String(n).padStart(2, '0');
  const ymdLocal = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const fmtPretty = d => d.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
  function startOfWeek(d){ const x=new Date(d); x.setHours(0,0,0,0); x.setDate(x.getDate()-x.getDay()); return x; }
  function endOfWeek(d){ const s=startOfWeek(d), e=new Date(s); e.setDate(s.getDate()+6); return e; }
  function highlightToday(cells, from){
    const todayStr = ymdLocal(new Date());
    cells.forEach((el,i)=>{ const cur=new Date(from); cur.setDate(from.getDate()+i);
      el.parentElement.classList.toggle('bg-indigo-50', ymdLocal(cur)===todayStr);
    });
  }

  async function loadWeek(){
    const from = startOfWeek(anchor);
    const to   = endOfWeek(anchor);
    rangeEl.textContent = `${fmtPretty(from)} – ${fmtPretty(to)}`;
    cntEls.forEach(el => el.textContent = '0');
    try{
      const url = new URL(endpoint, window.location.origin);
      url.searchParams.set('from', ymdLocal(from));
      url.searchParams.set('to',   ymdLocal(to));
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      const data = await res.json();
      for (let i=0;i<7;i++){
        const cur = new Date(from); cur.setDate(from.getDate()+i);
        const key = ymdLocal(cur); const n = data?.counts?.[key] ?? 0;
        cntEls[i].textContent = n;
      }
      highlightToday(cntEls, from);
    }catch(e){
      console.error(e); cntEls.forEach(el => el.textContent = '—');
    }
  }
  prevBtn.addEventListener('click',()=>{ anchor.setDate(anchor.getDate()-7); loadWeek(); });
  nextBtn.addEventListener('click',()=>{ anchor.setDate(anchor.getDate()+7); loadWeek(); });
  todayBtn.addEventListener('click',()=>{ anchor=new Date(); loadWeek(); });
  loadWeek(); setInterval(loadWeek, 30000);
})();
</script>

<script>
(() => {
  const btn = document.getElementById('btnAdminBook');
  if (!btn) return;

  const slotsEndpoint = @json(route('admin.chatbot-sessions.slots', $session->id));
  const bookEndpoint  = @json(route('admin.chatbot-sessions.book', $session->id));

  // --- helpers: sanitize & guard ---
  const DATE_RE = /^\d{4}-\d{2}-\d{2}$/;
  const TIME_RE = /^\d{2}:\d{2}$/;
  const pad = n => String(n).padStart(2,'0');

  function isWeekday(ymd){
    const [y,m,d] = ymd.split('-').map(Number);
    const dt = new Date(y, m-1, d);
    const day = dt.getDay(); // 0..6
    return day >= 1 && day <= 5;
  }
  function notPast(ymd){
    const [y,m,d] = ymd.split('-').map(Number);
    const dt = new Date(y, m-1, d, 23, 59, 59, 999);
    const now = new Date();
    return dt >= new Date(now.getFullYear(), now.getMonth(), now.getDate());
  }
  function plural(n){ return Number(n) === 1 ? 'slot' : 'slots'; }

  function renderModal(date='', counselors=[], slotsMap={}) {
    const options = (counselors || []).map(c => `<option value="${String(c.id).replace(/"/g,'')}">${String(c.name).replace(/</g,'&lt;')}</option>`).join('');
    return `
      <div style="text-align:left">
        <label class="text-sm font-medium text-slate-700">1) Pick date *</label>
        <input id="adm-date" type="date" value="${date}" min="{{ now()->toDateString() }}"
               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700">2) Counselor *</label>
            <select id="adm-counselor" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
              ${options}
            </select>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">3) Time *</label>
            <div id="adm-times" class="mt-1 grid grid-times gap-2" data-selected="" tabindex="0" aria-label="Available times"></div>
            <div id="adm-empty" class="text-xs text-slate-500 mt-1 hidden">No available times.</div>
          </div>
        </div>
      </div>
    `;
  }

  function buildTimePills(container, arr, selected='') {
    container.innerHTML = '';
    const emptyEl = document.getElementById('adm-empty');
    const times = Array.isArray(arr) ? arr : [];
    if (!times.length) { emptyEl.classList.remove('hidden'); container.dataset.selected=''; return; }
    emptyEl.classList.add('hidden');

    times.forEach(s => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'pill inline-flex flex-col items-center justify-center border border-slate-200 bg-white text-slate-800';
      b.dataset.value = s.value;

      const cap = Number.isFinite(s.pooled) ? s.pooled : 0;
      b.innerHTML = `<span class="time">${s.label}</span><span class="cap">(${cap} ${plural(cap)})</span>`;

      if (s.disabled) {
        b.disabled = true;
      } else {
        b.addEventListener('click', () => {
          [...container.querySelectorAll('button')].forEach(x=>x.classList.remove('pill--active'));
          b.classList.add('pill--active');
          container.dataset.selected = s.value;
          b.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
        });
        // keyboard selection
        b.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); b.click(); }
        });
      }

      if (!s.disabled && s.value === selected) b.classList.add('pill--active');
      container.appendChild(b);
    });

    if (selected && !times.some(t => !t.disabled && t.value === selected)) {
      container.dataset.selected = '';
    }
  }

  async function loadSlots(date) {
    const url = new URL(slotsEndpoint, window.location.origin);
    url.searchParams.set('date', date);
    const res = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    if (!res.ok) throw new Error('Failed to load slots');
    return await res.json(); // { counselors:[{id,name}], slots:{[cid]:[{value,label,disabled}]}, pooled:{'HH:MM':N} }
  }

  btn.addEventListener('click', async () => {
    try {
      const today = new Date();
      const defaultDate = `${today.getFullYear()}-${pad(today.getMonth()+1)}-${pad(today.getDate())}`;

      const first = await loadSlots(defaultDate);

      const { value: form } = await Swal.fire({
        title: 'Book appointment',
        html: renderModal(defaultDate, first.counselors || [], first.slots || {}),
        width: 780,
        showCancelButton: true,
        confirmButtonText: 'Confirm Booking',
        focusConfirm: false,
        didOpen: async () => {
          const dateEl   = document.getElementById('adm-date');
          const counEl   = document.getElementById('adm-counselor');
          const timeWrap = document.getElementById('adm-times');

          // Loading indicator
          const showLoading = (on=true) => {
            Swal.showLoading();
            if (!on) Swal.hideLoading();
          };

          let slotsMap  = first.slots  || {};
          let pooledMap = first.pooled || {};
          const compose = (cid, keepSelected=true) => {
            const prevSel = keepSelected ? (timeWrap.dataset.selected || '') : '';
            const items = (slotsMap?.[cid] || []).map(s => ({ ...s, pooled: pooledMap?.[s.value] ?? 0 }));
            buildTimePills(timeWrap, items, prevSel);
          };

          const refetch = async () => {
            const val = dateEl.value;
            // client-side date sanitation
            if (!DATE_RE.test(val) || !isWeekday(val) || !notPast(val)) {
              buildTimePills(timeWrap, [], '');
              return;
            }
            showLoading(true);
            try{
              const data = await loadSlots(val);
              slotsMap  = data.slots  || {};
              pooledMap = data.pooled || {};
              const list = data.counselors || [];
              const prev = counEl.value;
              counEl.innerHTML = list.map(c=>`<option value="${c.id}">${c.name}</option>`).join('');
              if (list.length) {
                const keep = list.some(c => String(c.id) === String(prev));
                counEl.value = keep ? prev : String(list[0].id);
              }
              compose(counEl.value, true);
            } finally {
              showLoading(false);
            }
          };

          // initial render
          compose(counEl.value);

          // changes
          dateEl.addEventListener('change', refetch);
          counEl.addEventListener('change', () => compose(counEl.value, false));

          // live polling every 5s while modal is open
          const poll = setInterval(refetch, 5000);
          const stop = () => clearInterval(poll);
          window.addEventListener('swal:willClose', stop, { once:true });

          // patch SweetAlert close to emit event
          const origClose = Swal.close;
          Swal.close = function() {
            window.dispatchEvent(new CustomEvent('swal:willClose'));
            return origClose.apply(this, arguments);
          };
        },
        preConfirm: () => {
          const date = /** @type {HTMLInputElement} */(document.getElementById('adm-date')).value;
          const counselorId = /** @type {HTMLSelectElement} */(document.getElementById('adm-counselor')).value;
          const time = /** @type {HTMLElement} */(document.getElementById('adm-times')).dataset.selected || '';

          // Strict sanitation (show real SweetAlert toasts instead of inline validation)
          if (!DATE_RE.test(date)) { swalToast('error','Invalid date format'); return false; }
          if (!isWeekday(date))    { swalToast('warning','Weekends are closed','Please choose Mon–Fri.'); return false; }
          if (!notPast(date))      { swalToast('warning','Pick a future date'); return false; }
          if (!TIME_RE.test(time)) { swalToast('error','Invalid time selected'); return false; }
          if (!counselorId)        { swalToast('warning','Please choose a counselor'); return false; }

          // Verify chosen time still exists for current counselor/date (prevents tampering)
          const buttons = Array.from(document.querySelectorAll('#adm-times button:not([disabled])'))
                              .map(b => b.dataset.value);
          if (!buttons.includes(time)) {
            swalToast('info','That time just filled','Please pick another slot.');
            return false;
          }

          return { date, counselorId, time };
        }
      });

      if (!form) return;

      const fd = new FormData();
      fd.append('_token', @json(csrf_token()));
      fd.append('date', form.date);
      fd.append('time', form.time);
      fd.append('counselor_id', form.counselorId);

      const resp = await fetch(bookEndpoint, { method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
      const data = await resp.json().catch(()=>({}));
      if (!resp.ok) throw new Error(data?.message || 'Booking failed.');

      Swal.fire({ icon:'success', title:'Booked', html:data.html || 'Appointment created.', confirmButtonText:'OK' })
        .then(() => window.location.reload());
    } catch (e) {
      console.error(e);
      Swal.fire({ icon:'error', title:'Unable to book', text: e.message || 'Please try again.' });
    }
  });
})();
</script>

<script>
  function swalToast(icon, title, text='') {
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon,
      title,
      text,
      timer: 2200,
      showConfirmButton: false,
    });
  }
</script>
@endpush
@endsection
