{{-- resources/views/admin/counselor-logs/pdf.blade.php --}}
@php
  // $rows: collection of log rows (counselor_name, month_year, students_list, students_count, common_dx)
  // $cName, $mName, $yName, $generatedAt
@endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Counselor Logs (PDF)</title>
  <style>
    * { box-sizing: border-box; font-family: DejaVu Sans; } /* <- single family only */
    body { margin: 18mm 14mm; font-size: 12px; color: #111827; }

    h1 { margin: 0 0 6px; font-size: 20px; }
    .meta { font-size: 11px; color: #6b7280; margin-bottom: 12px; }

    table { width:100%; border-collapse: collapse; }
    thead { background:#f1f5f9; color:#334155; }
    th, td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    th:last-child, td:last-child { text-align: right; }

    /* repeat header on each page */
    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    tr { page-break-inside: avoid; }

   
  </style>
</head>
<body>
  <h1>Counselor Logs</h1>
  <div class="meta">
    <strong>Counselor:</strong> {{ $cName }} &nbsp; | &nbsp;
    <strong>Month:</strong> {{ $mName }} &nbsp; | &nbsp;
    <strong>Year:</strong> {{ $yName }} &nbsp; • &nbsp;
    <span>generated: {{ $generatedAt }}</span>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:28%">Counselor</th>
        <th style="width:18%">Month / Year</th>
        <th style="width:36%">Students handled</th>
        <th style="width:18%; text-align:right;">Common diagnosis</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td><strong>{{ $r->counselor_name }}</strong></td>
          <td><span class="chip">{{ $r->month_year }}</span></td>
          <td>
            @if(!empty($r->students_list))
              {{ str_replace(' | ', ', ', $r->students_list) }}
              <span class="small"> &nbsp; ({{ (int)($r->students_count ?? 0) }} unique)</span>
            @else
              <span class="small">—</span>
            @endif
          </td>
          <td style="text-align:right;">{{ $r->common_dx ?: '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="small" style="text-align:center; padding:16px 0;">No records found.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
