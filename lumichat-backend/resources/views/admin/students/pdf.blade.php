{{-- resources/views/admin/students/pdf.blade.php --}}
@php
  /** @var \Illuminate\Support\Collection|array $students */
  $total = $students instanceof \Illuminate\Support\Collection
    ? $students->count()
    : (is_array($students) ? count($students) : (method_exists($students,'count') ? $students->count() : 0));
@endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Student Records (PDF)</title>
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
  <h1>Student Records</h1>
  <div class="meta">
    Filters:
    @if(!empty($q)) <strong>q:</strong> “{{ $q }}” | @endif
    @if(!empty($year)) <strong>year:</strong> {{ $year }} | @endif
    <strong>total:</strong> {{ $total }} &nbsp; • &nbsp;
    <span>generated: {{ $generatedAt ?? now()->format('Y-m-d H:i') }}</span>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:24%">Student Name</th>
        <th style="width:25%">Email</th>
        <th style="width:18%">Contact No.</th>
        <th style="width:15%">Course</th>
        <th style="width:18%; text-align:right;">Year Level</th>
      </tr>
    </thead>
    <tbody>
      @forelse($students as $s)
        <tr>
          <td><strong>{{ $s->name }}</strong></td>
          <td>{{ $s->email }}</td>
          <td>{{ $s->contact_number ?? '—' }}</td>
          <td>
            @if(!empty($s->course))
              <span class="chip">{{ $s->course }}</span>
            @else
              <span class="small">—</span>
            @endif
          </td>
          <td style="text-align:right;">
            @if(!empty($s->year_level))
              <span class="chip violet">{{ $s->year_level }}</span>
            @else
              <span class="small">—</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="small" style="text-align:center; padding:16px 0;">No students found.</td></tr>
      @endforelse
    </tbody>
  </table>

  <p class="small" style="margin-top:10px;">
    * This PDF lists all matching records based on current filters. Action buttons are omitted intentionally.
  </p>
</body>
</html>
