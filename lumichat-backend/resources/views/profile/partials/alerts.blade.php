<script>
document.addEventListener('DOMContentLoaded', () => {
  const hasSwal = typeof Swal !== 'undefined';

  let status = @json(session('status'));
  const error  = @json(session('error'));
  const warn   = @json(session('warning'));
  const infos  = @json(session('info'));
  const errors = @json($errors->all());

  // ✅ Map statuses to friendly text
  if (status === 'profile-updated')  status = 'Profile updated successfully';
  if (status === 'password-updated') status = 'Password updated successfully';
  if (status === 'account-deleted')  status = 'Account deleted permanently';

  const toast = (title, icon='info', timer=2500) => {
    if (!hasSwal || !title) return;
    Swal.fire({
      toast: true,
      position: 'top-end',  // ✅ right side
      showConfirmButton: false,
      timer,
      icon,
      title
    });
  };

  if (status) toast(status, 'success');
  if (warn)   toast(warn, 'warning');
  if (infos)  toast(infos, 'info');
  if (error)  toast(error, 'error', 2600);

  if (Array.isArray(errors) && errors.length && hasSwal) {
    const html = '<ul style="text-align:left;margin:0;padding-left:1rem">'
               + errors.map(e => `<li>• ${e}</li>`).join('')
               + '</ul>';
    Swal.fire({ icon:'error', title:'Please fix the following', html });
  }

  // Global helper
  window.toast = toast;
});
</script>
