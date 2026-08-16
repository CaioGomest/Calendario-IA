(function () {
  document.querySelectorAll('.form-cadastro').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var botao = form.querySelector('button[type="submit"]');
      var textoOriginal = botao.textContent;
      botao.disabled = true;
      botao.textContent = '...';

      fetch('cadastro', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
      })
        .then(function (r) { return r.json(); })
        .then(function (resposta) {
          if (resposta.ok) {
            form.classList.add('form-sai');
            setTimeout(function () {
              window.location.href = resposta.data.redirect;
            }, 260);
            return;
          }
          mostraErro(form, resposta.erro);
          botao.disabled = false;
          botao.textContent = textoOriginal;
        })
        .catch(function () {
          form.submit();
        });
    });
  });

  function mostraErro(form, mensagem) {
    var dica = form.querySelector('.dica-erro');
    dica.textContent = mensagem;
    dica.style.display = 'block';
    dica.classList.remove('treme');
    void dica.offsetWidth;
    dica.classList.add('treme');
  }
})();
