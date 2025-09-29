{{-- resources/views/admin/appointments/pdf-show.blade.php --}}
@php
  use Carbon\Carbon;

  $dt       = Carbon::parse($appointment->scheduled_at);
  $bookedAt = $appointment->created_at ? Carbon::parse($appointment->created_at) : null;

  $hasReport = isset($latestReport) && ($latestReport->diagnosis_result ?? '') !== '';
@endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Appointment #{{ $appointment->id }}</title>
  <style>
    /* Dompdf-safe */
    * { box-sizing: border-box; font-family: "DejaVu Sans"; }
    body { margin: 16mm 14mm; font-size: 12px; color: #111827; }

    /* Brand header (same as students PDF) */
    .brandbar { margin:0 0 8px; text-align:left; }
    .brand { display:inline-block; }
    .brand-logo { width:50px; height:50px; border-radius:50%; vertical-align:middle; }
    .brand-title { display:inline-block; vertical-align:middle; margin-left:10px; font:700 18px/1 "DejaVu Sans", sans-serif; white-space:nowrap; }

    h1   { margin: 8px 0 6px; font-size: 20px; }
    h2   { margin: 0 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #475569; }

    .meta { font-size: 11px; color: #6b7280; margin-bottom: 12px; }
    .chip { display:inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; border:1px solid #cbd5e1; color:#334155; background:#f8fafc; }

    table    { width:100%; border-collapse: collapse; }
    .card    { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
    .spacer  { height: 12px; }
    .hr      { height:1px; background:#e5e7eb; margin: 10px 0; }
    .small   { font-size: 11px; color:#6b7280; }
    .kv b    { display:block; font-size: 11px; color:#475569; text-transform:uppercase; margin-bottom: 2px; }
    .kv span { font-size: 13px; }

    /* Two-column layout */
    .twocol { width:100%; border-collapse: separate; border-spacing: 12px 0; }
    .twocol td { vertical-align: top; width: 50%; }

    .info { width:100%; border-collapse: collapse; }
    .info td { padding: 2px 0; vertical-align: top; }
    .section { margin-bottom: 10px; }
  </style>
</head>
<body>

  {{-- Brand header (logo + title side-by-side) --}}
  <div class="brandbar">
    <div class="brand">
      @if(!empty($logoData))
        <img class="brand-logo" src="{{ $logoData }}" alt="LumiCHAT">
      @endif
      <span class="brand-title">LumiCHAT</span>
    </div>
  </div>

  {{-- Title + meta --}}
  <h1>Appointment #{{ $appointment->id }}</h1>
  <div class="meta">
    Status: <span class="chip">{{ ucfirst($appointment->status) }}</span>
    &nbsp; • &nbsp;
    Created on: <strong>{{ $bookedAt ? $bookedAt->format('F d, Y · g:i A') : '—' }}</strong>
  </div>

  {{-- Two columns: Participants / Appointment Timing --}}
  <table class="twocol">
    <tr>
      <td>
        <div class="card">
          <h2>Participants</h2>

          <div class="section">
            <table class="info">
              <tr><td class="small" style="text-transform:uppercase;">Student</td></tr>
              <tr><td><strong>{{ $appointment->student_name }}</strong></td></tr>
              @if(!empty($appointment->student_email))
                <tr><td class="small">{{ $appointment->student_email }}</td></tr>
              @endif
              @if(!empty($appointment->student_id))
                <tr><td class="small">Student ID: {{ $appointment->student_id }}</td></tr>
              @endif
            </table>
          </div>

          <div class="section">
            <table class="info">
              <tr><td class="small" style="text-transform:uppercase;">Counselor</td></tr>
              <tr><td><strong>{{ $appointment->counselor_name }}</strong></td></tr>
              <tr>
                <td class="small">
                  {{ $appointment->counselor_email }}
                  @if(!empty($appointment->counselor_phone)) · {{ $appointment->counselor_phone }} @endif
                </td>
              </tr>
              @if(!empty($appointment->counselor_dept))
                <tr><td class="small">{{ $appointment->counselor_dept }}</td></tr>
              @endif
            </table>
          </div>
        </div>
      </td>

      <td>
        <div class="card">
          <h2>Appointment Timing</h2>

          <div class="kv">
            <b>Booked On</b>
            <span>{{ $bookedAt ? $bookedAt->format('F d, Y · g:i A') : '—' }}</span>
          </div>

          <div class="kv">
            <b>Scheduled For</b>
            <span>{{ $dt->format('F d, Y · g:i A') }}</span>
          </div>

          @if(!empty($appointment->location))
            <div class="kv">
              <b>Location</b>
              <span>{{ $appointment->location }}</span>
            </div>
          @endif
        </div>
      </td>
    </tr>
  </table>

  {{-- Optional Final Diagnosis --}}
  @if($hasReport)
    <div class="spacer"></div>
    <div class="card">
      <h2>Final Diagnosis (Report)</h2>
      <div class="kv">
        <b>Diagnosis</b>
        <span>{!! nl2br(e($latestReport->diagnosis_result)) !!}</span>
      </div>
      @if(($latestReport->notes ?? '') !== '')
        <div class="kv" style="margin-top:6px;">
          <b>Note</b>
          <span>{!! nl2br(e($latestReport->notes)) !!}</span>
        </div>
      @endif
    </div>
  @endif

</body>
</html>
