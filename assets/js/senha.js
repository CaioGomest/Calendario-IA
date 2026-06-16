(function () {
  document.querySelectorAll('[data-toggle-senha]').forEach(function (botao) {
    botao.addEventListener('click', function () {
      var input = botao.previousElementSibling;
      var visivel = input.type === 'text';
      input.type = visivel ? 'password' : 'text';
      botao.classList.toggle('ativo', !visivel);
    });
  });
})();
