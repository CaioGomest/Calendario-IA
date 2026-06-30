$(function () {

    function estaVisivel(el) {
        return el.offsetParent !== null;
    }

    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            desenhaDonut();
            desenhaBarras();
        });
    });

    function desenhaDonut() {
        $('.js-donut-chart').each(function () {
            if (!estaVisivel(this) || !window.finDonutData || !window.finDonutData.length) return;

            var tela = this;
            var ctx = tela.getContext('2d');
            var proporcao = window.devicePixelRatio || 1;
            var tamanho = 140;
            tela.width = tamanho * proporcao;
            tela.height = tamanho * proporcao;
            tela.style.width = tamanho + 'px';
            tela.style.height = tamanho + 'px';
            ctx.scale(proporcao, proporcao);

            var dados = window.finDonutData;
            var total = 0;
            for (var i = 0; i < dados.length; i++) total += dados[i].valor;
            if (total === 0) return;

            var centro_x = tamanho / 2, centro_y = tamanho / 2;
            var raio = tamanho / 2 - 4;
            var raio_interno = raio * 0.58;
            var inicio = -Math.PI / 2;

            for (var i = 0; i < dados.length; i++) {
                var fatia = (dados[i].valor / total) * Math.PI * 2;
                ctx.beginPath();
                ctx.moveTo(centro_x, centro_y);
                ctx.arc(centro_x, centro_y, raio, inicio, inicio + fatia);
                ctx.closePath();
                ctx.fillStyle = dados[i].cor;
                ctx.fill();
                inicio += fatia;
            }

            ctx.beginPath();
            ctx.arc(centro_x, centro_y, raio_interno, 0, Math.PI * 2);
            ctx.fillStyle = '#fff';
            ctx.fill();
        });
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

    function desenhaBarras() {
        $('.js-bar-chart').each(function () {
            if (!estaVisivel(this) || !window.finBarData || !window.finBarData.length) return;

            var tela = this;
            var ctx = tela.getContext('2d');
            var proporcao = window.devicePixelRatio || 1;
            var largura = tela.parentElement.clientWidth;
            var altura = 180;
            tela.width = largura * proporcao;
            tela.height = altura * proporcao;
            tela.style.width = largura + 'px';
            tela.style.height = altura + 'px';
            ctx.scale(proporcao, proporcao);

            var dados = window.finBarData;
            var maximo = 0;
            for (var i = 0; i < dados.length; i++) {
                if (dados[i].entradas > maximo) maximo = dados[i].entradas;
                if (dados[i].saidas > maximo) maximo = dados[i].saidas;
            }
            if (maximo === 0) maximo = 1;

            var marg_esq = 45, marg_dir = 10, marg_top = 10, marg_bot = 22;
            var largura_grafico = largura - marg_esq - marg_dir;
            var altura_grafico = altura - marg_top - marg_bot;
            var largura_grupo = largura_grafico / dados.length;
            var largura_barra = largura_grupo * 0.28;
            var espaco = 3;

            ctx.font = '600 10px Nunito, sans-serif';
            ctx.fillStyle = '#6a7585';
            ctx.textAlign = 'right';
            var passos = 4;
            for (var p = 0; p <= passos; p++) {
                var valor_escala = (maximo / passos) * p;
                var y_linha = marg_top + altura_grafico - (altura_grafico * (valor_escala / maximo));
                ctx.fillText('$' + Math.round(valor_escala / 1000) + 'k', marg_esq - 6, y_linha + 3);
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
                var altura_entrada = (dados[i].entradas / maximo) * altura_grafico;
                var altura_saida = (dados[i].saidas / maximo) * altura_grafico;

                desenhaBarraArredondada(ctx, x_grupo - largura_barra - espaco / 2, marg_top + altura_grafico - altura_entrada, largura_barra, altura_entrada, 3, '#4D7CFE');
                desenhaBarraArredondada(ctx, x_grupo + espaco / 2, marg_top + altura_grafico - altura_saida, largura_barra, altura_saida, 3, '#c7d6ff');

                ctx.fillStyle = '#6a7585';
                ctx.font = '600 10px Nunito, sans-serif';
                ctx.fillText(dados[i].mes, x_grupo, altura - 6);
            }
        });
    }

    $(document).on('input', '.js-fin-busca', function () {
        var termo = $(this).val().toLowerCase();
        $(this).closest('.fin-secao-transacoes').find('.fin-item').each(function () {
            var texto = $(this).text().toLowerCase();
            $(this).toggle(texto.indexOf(termo) > -1);
        });
    });

    $(document).on('click', '.fin-item[data-transacao]', function () {
        var transacao = $(this).data('transacao');
        $('#editar-id').val(transacao.id_transacao);
        $('#editar-id-deletar').val(transacao.id_transacao);
        $('#editar-tipo').val(transacao.tipo);
        $('#editar-valor').val(transacao.valor);
        $('#editar-descricao').val(transacao.descricao);
        $('#editar-categoria').val(transacao.categoria);
        $('#editar-data').val(transacao.data_transacao);
        $('#modal-editar').addClass('aberto');
    });

});
