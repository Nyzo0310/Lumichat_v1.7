<script>
document.addEventListener('DOMContentLoaded', () => {
  const hasSwal = typeof Swal !== 'undefined';

  const status = @json(session('status'));
  const error  = @json(session('error'));
  const warn   = @json(session('warning'));
  const infos  = @json(session('info'));
  const errors = @json($errors->all());

  const toast = (title, icon='info', timer=2200) => {
    if (!hasSwal || !title) return;
    Swal.fire({ toast:true, position:'top-end', showConfirmButton:false, timer, icon, title });
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

  // Expose a simple global helper if you want to use it elsewhere
  window.toast = toast;
});
</script>
