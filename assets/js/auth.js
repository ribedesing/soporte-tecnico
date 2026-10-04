// Mostrar / ocultar contraseña en las pantallas de autenticación
document.querySelectorAll('.password-toggle').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var input = document.getElementById(btn.dataset.target);
    var icon = btn.querySelector('i');
    if (!input) return;
    var mostrar = input.type === 'password';
    input.type = mostrar ? 'text' : 'password';
    icon.classList.toggle('bi-eye', !mostrar);
    icon.classList.toggle('bi-eye-slash', mostrar);
    var texto = mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';
    btn.setAttribute('aria-label', texto);
    btn.setAttribute('title', texto);
  });
});
