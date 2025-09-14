{{-- resources/views/profile/partials/alerts.blade.php --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof Swal === 'undefined') return;

  // ---------- helpers ----------
  const toast = (title, icon='info', timer=2400) => {
    if (!title) return;
    Swal.fire({ toast:true, position:'top-end', showConfirmButton:false, timer, icon, title });
  };
  const bulletList = arr =>
    '<ul style="text-align:left;margin:0;padding-left:1.1rem;">'
    + arr.map(m => `<li>• ${m}</li>`).join('') + '</ul>';

  // ---------- success toasts ----------
  let status = @json(session('status'));
  if (status === 'profile-updated')  toast('Profile updated', 'success');
  if (status === 'password-updated') toast('Password updated', 'success');
  if (status === 'account-deleted')  toast('Your account was deleted', 'success');

  const warn  = @json(session('warning'));
  const info  = @json(session('info'));
  const error = @json(session('error'));
  if (warn)  toast(warn, 'warning');
  if (info)  toast(info, 'info');
  if (error) toast(error, 'error', 3000);

  // ---------- error dialogs (bags) ----------
  const pwdErrors   = @json(optional($errors->getBag('updatePassword'))->all() ?? []);
  const delErrors   = @json(optional($errors->getBag('userDeletion'))->all() ?? []);
  const baseErrors  = @json(optional($errors->getBag('default'))->all() ?? []);

  const showErrors = (arr, afterClose) => {
    if (!arr || !arr.length) return;
    Swal.fire({
      icon: 'error',
      title: 'Please fix the following',
      html: bulletList(arr),
      confirmButtonText: 'OK'
    }).then(() => afterClose && afterClose());
  };

  if (pwdErrors.length) {
    // Show password update errors and guide the user to the form
    showErrors(pwdErrors, () => {
      const sec = document.getElementById('update-password-section');
      sec?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      document.getElementById('update_password_current_password')?.focus();
    });
  } else if (delErrors.length) {
    // Wrong password in delete-account modal, etc.
    showErrors(delErrors);
  } else if (baseErrors.length) {
    // Any other default-bag errors
    showErrors(baseErrors);
  }

  // Optional: expose toast globally if you need it elsewhere
  window.toast = toast;
});
</script>
