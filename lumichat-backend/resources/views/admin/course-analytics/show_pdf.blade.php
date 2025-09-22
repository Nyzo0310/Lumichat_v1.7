<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Course Analytics — {{ $title }}</title>
<style>
  * { box-sizing: border-box; font-family: "DejaVu Sans"; }
  body { margin: 18mm 14mm; font-size: 12px; color:#111827; }
</style>
</head>
<body>
  <h1>Course Analytics — {{ $title }}</h1>
  <div class="meta">Generated: {{ $generatedAt ?? now()->format('Y-m-d H:i') }}</div>

  <div class="card">
    <div class="row">
      <div class="col">
        <div class="muted" style="text-transform:uppercase; font-size:11px;">Course</div>
        <div style="font-weight:600">{{ $course->course ?? '—' }}</div>
      </div>
      <div class="col">
        <div class="muted" style="text-transform:uppercase; font-size:11px;">Year Level</div>
        <div>{{ $course->year_level ?? '—' }}</div>
      </div>
      <div class="col">
        <div class="muted" style="text-transform:uppercase; font-size:11px;">No. of Students</div>
        <div>{{ $course->student_count ?? '—' }}</div>
      </div>
    </div>
  </div>

  <div class="card">
    <h3>Common Diagnosis Breakdown</h3>
    @php $items = $course->breakdown ?? []; @endphp
    @if(is_array($items) && count($items))
      @foreach($items as $row)
        <div class="item">
          <div>{{ $row['label'] ?? '—' }}</div>
          <div style="font-weight:600">{{ $row['count'] ?? 0 }}</div>
        </div>
      @endforeach
    @else
      <div class="muted" style="text-align:center; padding:16px 0;">
        No breakdown available. This course has no compiled diagnosis data yet.
      </div>
    @endif
  </div>
</body>
</html>
