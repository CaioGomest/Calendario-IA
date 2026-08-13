(function () {
    function estaVisivel(el) {
        return el.offsetParent !== null;
    }

    function desenhaBarraArredondada(ctx, x, y, largura, altura, raio, cor) {
        if (altura < 1) return;
        raio = Math.min(raio, altura / 2, largura / 2);
        ctx.beginPath();
        ctx.moveTo(x + raio, y);
        ctx.lineTo(x + largura - raio, y);
        ctx.quadraticCurveTo(x + largura, y, x + largura, y + raio);
        ctx.lineTo(x + largura, y + altura);
        ctx.lineTo(x, y + altura);
        ctx.lineTo(x, y + raio);
        ctx.quadraticCurveTo(x, y, x + raio, y);
        ctx.closePath();
        ctx.fillStyle = cor;
        ctx.fill();
    }

    function desenhaGrafico(tela) {
        var dados;
        try { dados = JSON.parse(tela.dataset.dados || '[]'); } catch (e) { dados = []; }
        if (!estaVisivel(tela) || !dados.length) return;

        var cor = tela.dataset.cor || '#4D7CFE';
        var prefixo = tela.dataset.prefixo || '';

        var ctx = tela.getContext('2d');
        var proporcao = window.devicePixelRatio || 1;
        var largura = tela.parentElement.clientWidth;
        var altura = 160;
        tela.width = largura * proporcao;
        tela.height = altura * proporcao;
        tela.style.width = largura + 'px';
        tela.style.height = altura + 'px';
        ctx.scale(proporcao, proporcao);

        var maximo = 0;
        for (var i = 0; i < dados.length; i++) {
            if (dados[i].total > maximo) maximo = dados[i].total;
        }
        if (maximo === 0) maximo = 1;

        var marg_esq = 40, marg_dir = 10, marg_top = 10, marg_bot = 22;
        var largura_grafico = largura - marg_esq - marg_dir;
        var altura_grafico = altura - marg_top - marg_bot;
        var largura_grupo = largura_grafico / dados.length;
        var largura_barra = largura_grupo * 0.4;

        ctx.font = '600 10px Nunito, sans-serif';
        ctx.fillStyle = '#6a7585';
        ctx.textAlign = 'right';
        var passos = 4;
        for (var p = 0; p <= passos; p++) {
            var valor_escala = (maximo / passos) * p;
            var y_linha = marg_top + altura_grafico - (altura_grafico * (valor_escala / maximo));
            ctx.fillText(prefixo + Math.round(valor_escala), marg_esq - 6, y_linha + 3);
            ctx.strokeStyle = '#e6ebf4';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(marg_esq, y_linha);
            ctx.lineTo(largura - marg_dir, y_linha);
            ctx.stroke();
        }

        ctx.textAlign = 'center';
        for (var i = 0; i < dados.length; i++) {
            var x_grupo = marg_esq + largura_grupo * i + largura_grupo / 2;
            var altura_barra = (dados[i].total / maximo) * altura_grafico;
            desenhaBarraArredondada(ctx, x_grupo - largura_barra / 2, marg_top + altura_grafico - altura_barra, largura_barra, altura_barra, 3, cor);

            ctx.fillStyle = '#6a7585';
            ctx.font = '600 10px Nunito, sans-serif';
            ctx.fillText(dados[i].mes, x_grupo, altura - 6);
        }
    }

    function desenhaTodos() {
        document.querySelectorAll('.js-afiliado-bar').forEach(desenhaGrafico);
    }

    requestAnimationFrame(function () {
        requestAnimationFrame(desenhaTodos);
    });
})();
