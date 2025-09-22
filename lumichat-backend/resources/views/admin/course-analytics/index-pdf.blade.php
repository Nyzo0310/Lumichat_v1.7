<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Course Analytics</title>
  <style>
    * { box-sizing: border-box; font-family: DejaVu Sans, sans-serif; }
    body { margin: 18mm 14mm; font-size: 12px; color: #111827; }
    h1 { margin: 0 0 6px; font-size: 20px; }
    .meta { font-size: 11px; color: #6b7280; margin-bottom: 12px; }
    .chip { display:inline-block; padding:2px 8px; border-radius:12px; font-size:10px; border:1px solid #c7d2fe; color:#4338ca; background:#eef2ff; }
    .chip.violet { border-color:#ddd6fe; color:#6d28d9; background:#f5f3ff; }
    table { width:100%; border-collapse: collapse; }
    thead { background:#f1f5f9; color:#334155; }
    th, td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    th:last-child, td:last-child { text-align: right; }
    thead { display: table-header-group; } /* repeat header each page */
    tfoot { display: table-row-group; }
    tr { page-break-inside: avoid; }
    .small { font-size: 10px; color:#6b7280; }
  </style>
</head>
<body>
  <h1>Course Analytics</h1>
  <div class="meta">
    Filters — Year: <strong>{{ $yearKey }}</strong>
    @if(($q ?? '') !== '') | Search: <strong>{{ $q }}</strong>@endif
    <span style="float:right;">Generated: {{ $generatedAt }}</span>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:32%;">Course</th>
        <th style="width:12%;">Year</th>
        <th style="width:14%;">Students</th>
        <th style="width:42%;">Common Diagnosis</th>
      </tr>
    </thead>
    <tbody>
      @forelse($courses as $c)
        @php
          $course    = $c->course ?? '—';
          $year      = $c->year_level ?? '—';
          $count     = $c->student_count ?? '—';
          $list      = is_array($c->common_diagnoses ?? null) ? $c->common_diagnoses : [];
          $diagnoses = count($list) ? implode(', ', $list) : '—';
        @endphp
        <tr>
          <td><strong>{{ $course }}</strong></td>
          <td>{{ $year }}</td>
          <td>{{ $count }}</td>
          <td>{{ $diagnoses }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4" style="text-align:center; color:#666; padding:16px;">No course analytics found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
