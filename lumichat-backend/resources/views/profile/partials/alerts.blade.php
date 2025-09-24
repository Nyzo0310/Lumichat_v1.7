{{-- resources/views/profile/partials/alerts.blade.php --}}
<style>
  /* ===== ULTRA BIGGER TOAST ===== */
 /* Bigger toast card */
  .lumi-toast-xl.swal2-toast{
    padding: 24px 28px !important;     /* more breathing room */
    min-height: 70px !important;      /* taller */
    max-width: 1180px !important;      /* wider */
    gap: 1rem !important;              /* space between icon & text */
  }

  /* BIGGER ICON */
  .lumi-toast-xl .swal2-icon{
    width: 56px !important;
    min-width: 56px !important;
    height: 56px !important;
    margin: 0 !important;
    border: 0 !important;
    box-shadow: none !important;
  }

  /* If you use iconHtml (recommended), ensure the SVG area fills the box */
  .lumi-toast-xl .swal2-icon .swal2-icon-content{
    display: grid !important;
    place-items: center !important;
    width: 100% !important;
    height: 100% !important;
  }

  /* BIGGER TITLE */
  .lumi-toast-xl .swal2-title{
    margin: 0 !important;
    padding: 0 !important;
    font-size: 22px !important;        /* <- make this 24px if you want louder */
    font-weight: 900 !important;
    line-height: 1.25 !important;
  }

  /* Hide SweetAlert2's default success ring/lines */
  .lumi-toast-xl .swal2-success-ring,
  .lumi-toast-xl .swal2-success-fix,
  .lumi-toast-xl .swal2-success-line-tip,
  .lumi-toast-xl .swal2-success-line-long,
  .lumi-toast-xl .swal2-success-circular-line-left,
  .lumi-toast-xl .swal2-success-circular-line-right{ display:none !important; }

  .lumi-toast-xl .swal2-icon.swal2-info,
  .lumi-toast-xl .swal2-icon.swal2-warning,
  .lumi-toast-xl .swal2-icon.swal2-error{ border:0 !important; }

  /* Container: a bit more breathing room on big toasts */
  .swal2-container.swal2-top-end{
    background: transparent !important;
    backdrop-filter: none !important;
    pointer-events: none !important;
    padding-top: max(24px, env(safe-area-inset-top)) !important;
    padding-right: max(24px, env(safe-area-inset-right)) !important;
    padding-bottom: 20px !important;
    padding-left: 16px !important;
    z-index: 2147483600 !important;
  }
  .swal2-container.swal2-top-end .swal2-popup{
    pointer-events: auto !important;
  }

  /* Thicker progress bar to suit larger toast (optional) */
  .swal2-timer-progress-bar { height: 4px !important; }

  /* Mobile safety: scale down slightly on very narrow screens */
  @media (max-width: 420px){
    .lumi-toast-xl{
      padding: 18px 20px !important;
      min-height: 96px !important;
      max-width: 92vw !important;
    }
    .lumi-toast-xl .swal2-title{ font-size: 18px !important; gap: .75rem !important; }
    .lumi-toast-xl .swal2-icon{ width: 40px !important; height: 40px !important; min-width: 40px !important; }
  }
</style>


<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof Swal === 'undefined') return;

  /* ---- ULTRA BIG Toast mixin ---- */
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4200,
    timerProgressBar: true,
    backdrop: false,
    customClass: { popup: 'lumi-toast-xl' },
    didOpen: (el) => {
      el.addEventListener('mouseenter', Swal.stopTimer);
      el.addEventListener('mouseleave', Swal.resumeTimer);
    }
  });

  /* 36px inline SVG icons */
  const ICONS = {
    success: `
      <svg width="36" height="36" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="10" fill="none" stroke="rgb(16,185,129)" stroke-width="2"></circle>
        <path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="rgb(16,185,129)" stroke-width="2.8"
              stroke-linecap="round" stroke-linejoin="round"></path>
      </svg>`,
    error: `
      <svg width="36" height="36" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="10" fill="none" stroke="rgb(239,68,68)" stroke-width="2"></circle>
        <path d="M8 8l8 8M16 8l-8 8" stroke="rgb(239,68,68)" stroke-width="2.8" stroke-linecap="round"></path>
      </svg>`,
    warning: `
      <svg width="36" height="36" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 3l10 18H2L12 3z" fill="none" stroke="rgb(234,179,8)" stroke-width="2"></path>
        <path d="M12 9v6" stroke="rgb(234,179,8)" stroke-width="2.8" stroke-linecap="round"></path>
        <circle cx="12" cy="17" r="1.35" fill="rgb(234,179,8)"></circle>
      </svg>`,
    info: `
      <svg width="36" height="36" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="10" fill="none" stroke="rgb(59,130,246)" stroke-width="2"></circle>
        <path d="M12 10v6" stroke="rgb(59,130,246)" stroke-width="2.8" stroke-linecap="round"></path>
        <circle cx="12" cy="7" r="1.5" fill="rgb(59,130,246)"></circle>
      </svg>`
  };

  /* Global helper — OVERRIDES any older/smaller toast helpers */
  function toast(title, type = 'info', timer = 4200){
    if (!title) return;
    Toast.fire({
      // icon: type,                     // ❌ remove this
      iconHtml: ICONS[type] || ICONS.info, // ✅ only our SVG
      title,
      timer
    });
  }

  const bulletList = arr =>
    '<ul style="text-align:left;margin:0;padding-left:1.1rem;">'
    + (arr || []).map(m => `<li>• ${m}</li>`).join('') + '</ul>';

  /* ---- Session-driven toasts ---- */
  const status = @json(session('status'));
  if (status === 'profile-updated')  toast('Profile updated', 'success', 4800);
  if (status === 'password-updated') toast('Password updated', 'success', 4800);
  if (status === 'account-deleted')  toast('Your account was deleted', 'success', 4800);

  const warn  = @json(session('warning'));
  const info  = @json(session('info'));
  const error = @json(session('error'));
  if (warn)  toast(warn,  'warning', 5200);
  if (info)  toast(info,  'info',    5200);
  if (error) toast(error, 'error',   5600);

  /* ---- Validation error MODALS (with backdrop) ---- */
  const pwdErrors  = @json(optional($errors->getBag('updatePassword'))->all() ?? []);
  const delErrors  = @json(optional($errors->getBag('userDeletion'))->all() ?? []);
  const baseErrors = @json(optional($errors->getBag('default'))->all() ?? []);

  function showErrors(arr, afterClose){
    if (!arr || !arr.length) return;
    Swal.fire({
      icon: 'error',
      title: 'Please fix the following',
      html: bulletList(arr),
      confirmButtonText: 'OK'
    }).then(() => afterClose && afterClose());
  }

  if (pwdErrors.length){
    showErrors(pwdErrors, () => {
      const sec = document.getElementById('update-password-section');
      sec?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      document.getElementById('update_password_current_password')?.focus();
    });
  } else if (delErrors.length){
    showErrors(delErrors);
  } else if (baseErrors.length){
    showErrors(baseErrors);
  }

  /* Expose the BIG helper globally */
  window.toast = toast;
  window.lumiToast = toast;
});
</script>
