// Show/hide password toggle
document.querySelectorAll('.input-toggle').forEach(btn => {
  btn.addEventListener('click', function() {
    const input = this.parentElement.querySelector('.form-input');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('ri-eye-line');
      icon.classList.add('ri-eye-off-line');
    } else {
      input.type = 'password';
      icon.classList.remove('ri-eye-off-line');
      icon.classList.add('ri-eye-line');
    }
  });
});