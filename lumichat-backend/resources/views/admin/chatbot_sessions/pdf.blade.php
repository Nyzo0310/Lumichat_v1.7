<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Chatbot Sessions</title>
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
  <h1>Chatbot Sessions</h1>
  <div class="meta">
    Filters — Date: <strong>{{ $dateKey }}</strong>
    @if($q !== '') | Search: <strong>{{ $q }}</strong>@endif
    <span style="float:right;">Generated: {{ $generatedAt }}</span>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:20%;">Session ID</th>
        <th style="width:28%;">Student</th>
        <th style="width:36%;">Initial Result</th>
        <th style="width:16%;">Initial Date</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $s)
        @php
          $code = 'LMC-' . now()->format('Y') . '-' . str_pad($s->id, 4, '0', STR_PAD_LEFT);
        @endphp
        <tr>
          <td>{{ $code }}</td>
          <td>{{ $s->user->name ?? '—' }}</td>
          <td>{{ $s->topic_summary ?? '—' }}</td>
          <td>{{ optional($s->created_at)->format('M d, Y') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4" style="text-align:center; color:#666; padding:18px;">No sessions found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
