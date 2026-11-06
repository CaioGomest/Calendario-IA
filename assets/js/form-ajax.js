function enviaFormularioAjax(form, aoSucesso, aoErro) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var botao = form.querySelector('button[type="submit"]');
        var textoOriginal = botao ? botao.textContent : '';
        if (botao) {
            botao.disabled = true;
            botao.textContent = '...';
        }

        fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form)
        })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                if (resp.ok) {
                    aoSucesso(resp.data || {});
                    return;
                }
                if (botao) {
                    botao.disabled = false;
                    botao.textContent = textoOriginal;
                }
                aoErro(resp.erro);
            })
            .catch(function () {
                form.submit();
            });
    });
}

function navegaComTransicao(form, url) {
    form.classList.add('form-sai');
    setTimeout(function () {
        window.location.href = url;
    }, 260);
}

function mostraErroForm(form, mensagem) {
    var erro = form.previousElementSibling;
    if (!erro || !erro.classList.contains('erro-msg')) {
        erro = document.createElement('div');
        erro.className = 'erro-msg';
        erro.style.marginBottom = '10px';
        form.parentNode.insertBefore(erro, form);
    }
    erro.textContent = mensagem;
}
