<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Course Analytics — {{ $title }}</title>
  <style>
    *{ box-sizing:border-box; font-family:"DejaVu Sans"; }
    body{ margin:16mm 14mm; font-size:12.5px; color:#111827; line-height:1.45; }

    .brandbar{ margin:0 0 10px; text-align:left; }
    .brand{ display:inline-block; }
    .brand-logo{ width:50px; height:50px; border-radius:50%; vertical-align:middle; }
    .brand-title{ display:inline-block; vertical-align:middle; margin-left:10px; font:700 18px/1 DejaVu Sans, sans-serif; white-space:nowrap; }

    h1{ margin:10px 0 6px; font-size:20px; }
    .meta{ font-size:11px; color:#6b7280; margin-bottom:12px; }

    .card{ border:1px solid #e5e7eb; border-radius:8px; padding:12px; margin-bottom:12px; }
    .row{ display:table; width:100%; table-layout:fixed; }
    .col{ display:table-cell; padding-right:12px; vertical-align:top; }
    .col:last-child{ padding-right:0; }
    .muted{ color:#6b7280; }
    .item{ display:table; width:100%; table-layout:fixed; border-bottom:1px solid #f1f5f9; padding:8px 0; }
    .item > div{ display:table-cell; }
    .item > div:last-child{ text-align:right; width:70px; }
  </style>
</head>
<body>

  <div class="brandbar">
    <div class="brand">
      @if(!empty($logoData)) <img class="brand-logo" src="{{ $logoData }}" alt="LumiCHAT">@endif
      <span class="brand-title">LumiCHAT</span>
    </div>
  </div>

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
    <h3 style="margin:0 0 8px; font-size:14px;">Common Diagnosis Breakdown</h3>
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
