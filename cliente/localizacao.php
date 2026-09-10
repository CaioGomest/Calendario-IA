<?php
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';
require_once __DIR__ . '/../funcoes/funcoesComponentes.php';

iniciaSessao();
exigeLoginCliente();

$usuario = buscaUsuarioPorId(usuarioLogadoId());
$idiomas_disponiveis = listaIdiomasDisponiveis();
$fusos_disponiveis = listaFusosHorariosComuns();

$eh_ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $idioma = $_POST['idioma'] ?? '';
    $fuso = $_POST['fuso_horario'] ?? '';

    if (in_array($idioma, $idiomas_disponiveis, true)) {
        atualizaIdiomaUsuario($usuario['id_usuario'], $idioma);
        $_SESSION['idioma'] = $idioma;
    }
    if (in_array($fuso, $fusos_disponiveis, true)) {
        atualizaFusoHorarioUsuario($usuario['id_usuario'], $fuso);
    }

    if ($eh_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'data' => ['redirect' => 'pago']]);
        exit;
    }
    header('Location: pago');
    exit;
}

$idioma_atual = $_SESSION['idioma'];
$fuso_atual = $usuario['fuso_horario'];
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="view-transition" content="same-origin" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg" />
<title><?= htmlspecialchars(nomeApp()) ?> - <?= traduz('localizacao_titulo') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/cliente.css?v=<?= versaoAsset('assets/css/cliente.css') ?>" />
</head>
<body>

<?php
$form_localizacao = function () use ($idiomas_disponiveis, $fusos_disponiveis, $idioma_atual, $fuso_atual) { ?>
    <form method="post" action="localizacao" class="form-transicao form-localizacao" style="width:100%;">
      <input type="hidden" name="acao" value="salvar" />
      <div class="campo" style="margin-top:0;">
        <label><?= traduz('idioma_titulo') ?></label>
        <div class="input">
          <select name="idioma" style="border:none;width:100%;background:none;">
            <?php foreach ($idiomas_disponiveis as $codigo): ?>
            <option value="<?= htmlspecialchars($codigo) ?>" <?= $codigo === $idioma_atual ? 'selected' : '' ?>><?= htmlspecialchars(nomeIdioma($codigo)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="campo">
        <label><?= traduz('fuso_titulo') ?></label>
        <div class="input">
          <select name="fuso_horario" style="border:none;width:100%;background:none;">
            <?php foreach ($fusos_disponiveis as $fuso): ?>
            <option value="<?= htmlspecialchars($fuso) ?>" <?= $fuso === $fuso_atual ? 'selected' : '' ?>><?= htmlspecialchars(nomeFusoHorario($fuso)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" class="botao botao-primario botao-espaco" style="width:100%;"><?= traduz('botao_continuar') ?></button>
    </form>
    <div class="link-central" style="margin-top:12px;"><a class="link" href="pago"><?= traduz('pago_pular') ?></a></div>
<?php };
?>

<div class="vista-mobile">
  <div class="barra-topo">
    <?php renderizaMarca('ink', 20); ?>
  </div>
  <div class="conteudo-pagina espacado centralizado">
    <div class="etapa-cabecalho" style="width:100%;">
      <div class="progresso"><span class="completo"></span><span class="completo"></span><span></span><span></span><span></span></div>
      <span class="etapa-rotulo"><?= traduz('localizacao_step_label') ?></span>
    </div>
    <div class="icone-grande">🌐</div>
    <h1 class="tela-titulo"><?= traduz('localizacao_titulo') ?></h1>
    <p class="tela-subtitulo" style="max-width:30ch;"><?= traduz('localizacao_subtitulo') ?></p>
    <?php $form_localizacao(); ?>
  </div>
</div>

<div class="vista-desktop">
  <div class="cadastro-estrutura">
    <div class="cadastro-marca">
      <?php renderizaMarca('white', 22); ?>
      <div class="login-icone" style="font-size:46px;">🌐</div>
      <h2><?= traduz('localizacao_titulo') ?></h2>
      <p><?= traduz('localizacao_subtitulo') ?></p>
    </div>
    <div class="cadastro-form">
      <div class="form-area">
        <div class="cadastro-progresso">
          <div class="cadastro-progresso-barras"><span class="completo"></span><span class="completo"></span><span></span><span></span><span></span></div>
          <span class="cadastro-progresso-rotulo"><?= traduz('localizacao_step_label') ?></span>
        </div>
        <h2 class="form-titulo"><?= traduz('localizacao_titulo') ?></h2>
        <p class="form-subtitulo"><?= traduz('localizacao_subtitulo') ?></p>
        <?php $form_localizacao(); ?>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/mascote.js"></script>
<script src="../assets/js/form-ajax.js"></script>
<script>
document.querySelectorAll('.form-localizacao').forEach(function (form) {
    enviaFormularioAjax(form, function (data) {
        navegaComTransicao(form, data.redirect);
    }, function () {});
});
</script>
</body>
</html>
