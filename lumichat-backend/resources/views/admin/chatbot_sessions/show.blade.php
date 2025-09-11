@extends('layouts.admin')
@section('title','Chatbot Session')

@section('content')
@php
  $codeYear = $session->created_at?->format('Y') ?? now()->format('Y');
  $code     = 'LMC-' . $codeYear . '-' . str_pad($session->id, 4, '0', STR_PAD_LEFT);
@endphp

<div class="max-w-5xl mx-auto p-6 space-y-6">

  {{-- Header row (Back + Print) --}}
  <div class="flex items-center justify-between no-print">
    <h2 class="text-2xl font-bold tracking-tight text-slate-800">Chatbot Session</h2>
    <div class="flex items-center gap-2">
        <button type="button"
              class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-slate-800 hover:bg-slate-50"
              onclick="printNode('#sessionPrintable', 'Chatbot Session {{ $code }}')">
        Print
      </button>
      <a href="{{ route('admin.chatbot-sessions.index') }}"
         class="text-sm text-indigo-600 hover:underline">← Back to list</a>
    </div>
  </div>

  {{-- PRINTABLE AREA (summary + weekly counts) --}}
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

{{-- tiny helper to copy text --}}
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
/* ---------- Print helper shared by both pages ---------- */
function printNode(selector, title = document.title) {
  const node = document.querySelector(selector) || document.body;
  const w = window.open('', '_blank', 'width=1024,height=700');
  const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
    .map(n => n.outerHTML).join('\n');
  w.document.write(`
    <html>
      <head>
        <meta charset="utf-8">
        <title>${title}</title>
        ${styles}
        <style>
          @page { margin: 1.2cm; }
          @media print {
            .no-print { display: none !important; }
            body { background:#fff !important; }
            .print-area { box-shadow:none!important; }
          }
        </style>
      </head>
      <body>${node.outerHTML}</body>
    </html>
  `);
  w.document.close(); w.focus(); w.onload = () => w.print();
}

/* ---------- Weekly counts JS (unchanged) ---------- */
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
@endpush
@endsection
