@extends('layouts.admin')
@section('title', 'Student Details')

@section('content')
<div class="max-w-5xl mx-auto p-6 space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <h2 class="text-2xl font-semibold">Student Details</h2>
    <div class="flex items-center gap-2 screen-only">
      <button type="button"
              onclick="printStudentDetails()"
              class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700">
        Print
      </button>
      <a href="{{ route('admin.students.index') }}"
         class="text-sm text-indigo-600 hover:underline">← Back to list</a>
    </div>
  </div>

  {{-- ===== PRINT SCOPE START ===== --}}
  <div id="print-details-root" class="space-y-6">

    {{-- print-only title --}}
    <h1 class="print-title hidden">
      Student Details — {{ $student->full_name }}
    </h1>

    {{-- Chart: Appointments by Month (selectable year) --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div class="flex items-center gap-3">
          <h3 class="text-lg font-semibold text-gray-900">
            Appointments — <span class="font-normal text-gray-600">Monthly totals</span>
          </h3>
          @if(isset($total))
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">
              Total: {{ $total }}
            </span>
          @endif
          @isset($peakLabel)
            <span class="hidden sm:inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
              Peak: {{ $peakLabel }}
            </span>
          @endisset
        </div>

        {{-- Year selector (screen only; hidden on print) --}}
        <form method="GET" action="{{ route('admin.students.show', $student->id) }}" class="flex items-center gap-2 screen-only">
          <input type="hidden" name="year" id="yearInput" value="{{ $year }}">
          @php
            $minYear = min($yearsAvailable);
            $maxYear = max($yearsAvailable);
          @endphp
          <button type="button"
                  class="rounded-lg border px-2.5 py-1 text-sm disabled:opacity-40"
                  onclick="bumpYear(-1)"
                  {{ $year <= $minYear ? 'disabled' : '' }}
                  aria-label="Previous year">‹</button>

          <label for="yearSelect" class="text-sm text-gray-600">Year</label>
          <select id="yearSelect"
                  class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                  onchange="document.getElementById('yearInput').value=this.value; this.form.submit()">
            @foreach ($yearsAvailable as $y)
              <option value="{{ $y }}" @selected($year === (int)$y)>{{ $y }}</option>
            @endforeach
          </select>

          <button type="button"
                  class="rounded-lg border px-2.5 py-1 text-sm disabled:opacity-40"
                  onclick="bumpYear(1)"
                  {{ $year >= $maxYear ? 'disabled' : '' }}
                  aria-label="Next year">›</button>
        </form>
      </div>

      <div class="relative h-72 md:h-80">
        <canvas id="studentApptsChart" role="img" aria-label="Bar chart of monthly appointments for year {{ $year }}"></canvas>

        {{-- Empty state --}}
        @if (($total ?? 0) === 0)
          <div class="absolute inset-0 grid place-items-center">
            <div class="text-center text-sm text-gray-500">
              No appointments recorded for {{ $year }}.
            </div>
          </div>
        @endif
      </div>
    </div>

    {{-- Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8 space-y-6 border">

      {{-- Info Grid --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <p class="text-sm text-gray-500">FULL NAME</p>
          <p class="text-lg font-medium">{{ $student->full_name }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">EMAIL</p>
          <p class="text-lg font-medium">{{ $student->email }}</p>
        </div>

        <div>
          <p class="text-sm text-gray-500">CONTACT NUMBER</p>
          <p class="text-lg font-medium">{{ $student->contact_number }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">COURSE</p>
          <p class="text-lg font-medium">{{ $student->course }}</p>
        </div>

        <div>
          <p class="text-sm text-gray-500">YEAR LEVEL</p>
          <p class="text-lg font-medium">{{ $student->year_level }}</p>
        </div>
      </div>

      {{-- Dates --}}
      <div class="border-t pt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <p class="text-sm text-gray-500">CREATED</p>
          <p class="text-lg font-medium">{{ $student->created_at->format('F d, Y • h:i A') }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-500">UPDATED</p>
          <p class="text-lg font-medium">{{ $student->updated_at->format('F d, Y • h:i A') }}</p>
        </div>
      </div>

      {{-- Actions (screen only; hide on print) --}}
      <div class="flex gap-4 pt-4 screen-only">
        <a href="mailto:{{ $student->email }}"
           class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
          Email Student
        </a>
        <button onclick="navigator.clipboard.writeText('{{ $student->email }}')"
                class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
          Copy Email
        </button>
      </div>
    </div>
  </div>
  {{-- ===== PRINT SCOPE END ===== --}}
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // expose year bump globally (your buttons use onclick)
  window.bumpYear = function bumpYear(delta) {
    const sel = document.getElementById('yearSelect');
    const values = Array.from(sel.options).map(o => parseInt(o.value, 10)); // DESC
    const current = parseInt(sel.value, 10);
    const pos = values.indexOf(current);
    const target = values[pos + (delta > 0 ? -1 : +1)]; // because values are DESC
    if (typeof target !== 'undefined') {
      sel.value = String(target);
      document.getElementById('yearInput').value = String(target);
      sel.form.submit();
    }
  };

  (function () {
    const canvas = document.getElementById('studentApptsChart');
    if (!canvas) return;

    // Use the server-injected series/labels, but coerce to numbers
    const series = (@json($series ?? [])).map(v => parseInt(v, 10) || 0);
    const labels = @json($labels ?? []);
    const total  = series.reduce((a, b) => a + b, 0);

    // Kill any previous chart bound to this canvas (bfCache/Livewire/PJAX/etc.)
    if (window.Chart && Chart.getChart) {
      const prev = Chart.getChart(canvas);
      if (prev) prev.destroy();
    }

    // If no data -> don't draw a chart; show the empty state only
    if (total === 0) {
      // make sure canvas stays clear
      const ctx = canvas.getContext('2d');
      ctx && ctx.clearRect(0, 0, canvas.width, canvas.height);
      return;
    }

    const chart = new Chart(canvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          data: series,
          borderColor: '#4f46e5',
          backgroundColor: 'rgba(99,102,241,0.35)',
          hoverBackgroundColor: 'rgba(99,102,241,0.55)',
          borderWidth: 1.5,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 300 },
        elements: { bar: { borderRadius: 6 } },
        scales: {
          x: { grid: { display: false }, ticks: { color: '#334155' } },
          y: { beginAtZero: true, ticks: { precision: 0, color: '#334155' }, grid: { color: 'rgba(148,163,184,0.25)' } }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#111827', padding: 10, displayColors: false,
            callbacks: {
              title: items => `Month: ${items[0].label}`,
              label: ctx => {
                const y = ctx.parsed.y || 0;
                return `${y} appointment${y === 1 ? '' : 's'}`;
              }
            }
          },
          title: {
            display: true, text: 'Appointments in ' + @json($year),
            color: '#0f172a', font: { size: 14, weight: '600' }, padding: { top: 4, bottom: 10 }
          }
        }
      }
    });

    // Keep a reference for print sizing
    window.__studentApptsChart = chart;
  })();

  // Simple print handler (also resizes the chart so canvas is painted)
  window.printStudentDetails = function(){
    try { window.__studentApptsChart && window.__studentApptsChart.resize(); } catch(e){}
    window.print();
  };
  window.addEventListener('beforeprint', function(){
    try { window.__studentApptsChart && window.__studentApptsChart.resize(); } catch(e){}
  });
</script>
@endpush
