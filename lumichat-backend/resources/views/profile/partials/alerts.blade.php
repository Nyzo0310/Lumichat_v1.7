{{-- resources/views/profile/partials/alerts.blade.php --}}
@once
@push('styles')
<style>
  /* Bullet list used inside pretty error modal */
  .swal-bullets{margin:.35rem 0 0;padding:0;list-style:none;line-height:1.7;font-size:.98rem;color:#475569}
  .swal-bullets li{display:flex;gap:.5rem;align-items:flex-start}
  .swal-bullets li>span:first-child{line-height:1.7}

  /* Indigo primary OK button to match the app */
  .swal2-confirm.btn-primary-ghost{
    background:#4f46e5!important;color:#fff!important;border-radius:.65rem!important;
    padding:.6rem 1.1rem!important;box-shadow:0 8px 20px rgba(79,70,229,.25)!important;
  }
  .swal2-confirm.btn-primary-ghost:hover{filter:brightness(.96)}
</style>
@endpush
@endonce

@once
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof Swal === 'undefined') return;

  /* ---------------- Toast helper ---------------- */
  const toast = (title, icon='info', timer=2400) => {
    if (!title) return;
    Swal.fire({ toast:true, position:'top-end', showConfirmButton:false, timer, icon, title });
  };
  // Expose globally if you want to call it elsewhere
  window.toast = toast;

  /* ---------------- Pretty error modal ---------------- */
  function prettyError(items){
    const crossIcon = `
      <div style="width:84px;height:84px;margin:0 auto 12px;position:relative;">
        <div style="position:absolute;inset:0;border-radius:50%;
                    box-shadow:0 0 0 6px rgba(239,68,68,.12), inset 0 0 0 2px rgba(239,68,68,.35);
                    animation:pulseRing 1.8s ease-out infinite;"></div>
        <div style="position:absolute;inset:10px;border-radius:50%;background:#fff;display:flex;
                    align-items:center;justify-content:center;border:2px solid #fca5a5">
          <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.6"
               stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </div>
      </div>
      <style>
        @keyframes pulseRing{
          0%{box-shadow:0 0 0 6px rgba(239,68,68,.12), inset 0 0 0 2px rgba(239,68,68,.35)}
          70%{box-shadow:0 0 0 16px rgba(239,68,68,0), inset 0 0 0 2px rgba(239,68,68,.35)}
          100%{box-shadow:0 0 0 6px rgba(239,68,68,0), inset 0 0 0 2px rgba(239,68,68,.35)}
        }
      </style>
    `;

    const list = `<ul class="swal-bullets">${
      (items||[]).map(e => `<li><span>•</span><span>${String(e).replace(/hypens/ig,'hyphens')}</span></li>`).join('')
    }</ul>`;

    Swal.fire({
      html: `
        <h2 style="margin:0 0 .55rem;font-size:1.55rem;font-weight:800;color:#0f172a;letter-spacing:.2px;text-align:center;">
          Please fix the following
        </h2>
        ${crossIcon}
        ${list}
      `,
      showConfirmButton:true,
      confirmButtonText:'OK',
      width:540,
      padding:'1.2rem 1.2rem 1.4rem',
      background:'#ffffff',
      customClass:{ popup:'rounded-2xl shadow-2xl', confirmButton:'swal2-confirm btn-primary-ghost' }
    });
  }

  /* ---------------- Success toasts ----------------
     Prefer a human-friendly 'success' message; fallback to mapping 'status' codes. */
  const status  = @json(session('status'));
  const success = @json(session('success')); // e.g., "Account has been successfully deleted."
  const statusMap = {
    'profile-updated' : 'Profile updated',
    'password-updated': 'Password updated',
    'account-deleted' : 'Your account was deleted'
  };

  if (success) {
    toast(success, 'success');
  } else if (status && statusMap[status]) {
    toast(statusMap[status], 'success');
  }

  // Optional other one-off toasts
  const warn  = @json(session('warning'));
  const info  = @json(session('info'));
  const error = @json(session('error'));
  if (warn)  toast(warn,  'warning');
  if (info)  toast(info,  'info');
  if (error) toast(error, 'error', 3000);

  /* ---------------- Error bags -> enhanced dialog ---------------- */
  const pwdErrors  = @json(optional($errors->getBag('updatePassword'))->all() ?? []);
  const delErrors  = @json(optional($errors->getBag('userDeletion'))  ->all() ?? []);
  const baseErrors = @json(optional($errors->getBag('default'))       ->all() ?? []);

  if (pwdErrors.length)      prettyError(pwdErrors);
  else if (delErrors.length) prettyError(delErrors);
  else if (baseErrors.length)prettyError(baseErrors);
});
</script>
@endpush
@endonce
