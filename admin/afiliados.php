<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesAfiliados.php';
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'afiliados';
$msg_sucesso = '';
$msg_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'criar') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $comissao_percentual = $_POST['comissao_percentual'] ?? '10';

    if (!$nome || !$email || !$senha) {
        $msg_erro = 'Preencha todos os campos.';
    } elseif (strlen($senha) < 6) {
        $msg_erro = 'A senha deve ter ao menos 6 caracteres.';
    } elseif (buscaAfiliadoPorEmail($email)) {
        $msg_erro = 'Já existe um afiliado com este e-mail.';
    } else {
        insereAfiliado([
            'nome' => $nome,
            'email' => $email,
            'senha' => $senha,
            'comissao_percentual' => $comissao_percentual,
        ]);
        $msg_sucesso = 'Afiliado criado com sucesso.';
    }

    if ($msg_sucesso) {
        header('Location: afiliados.php?sucesso=' . urlencode($msg_sucesso));
        exit;
    }
}

if (!empty($_GET['sucesso'])) {
    $msg_sucesso = $_GET['sucesso'];
}

$afiliados = listaAfiliados();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars(nomeApp()) ?> — Afiliados</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= versaoAsset('assets/css/admin.css') ?>" />
</head>
<body>
<div class="admin-estrutura">
  <?php require __DIR__ . '/_includes/sidebar.php'; ?>
  <div class="admin-conteudo">
    <header class="admin-barra">
      <div>
        <h1>Afiliados</h1>
        <div class="subtitulo"><?= count($afiliados) ?> afiliado(s)</div>
      </div>
      <div class="espaco"></div>
      <button type="button" class="botao-cabecalho" onclick="document.getElementById('modal-criar').classList.add('aberto')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Novo afiliado
      </button>
    </header>
    <div class="admin-area">

      <?php if ($msg_sucesso): ?>
      <div class="sucesso-msg"><?= htmlspecialchars($msg_sucesso) ?></div>
      <?php endif; ?>
      <?php if ($msg_erro): ?>
      <div class="erro-msg" style="margin-bottom:14px;"><?= htmlspecialchars($msg_erro) ?></div>
      <?php endif; ?>

      <div class="painel">
        <div class="painel-cabecalho">
          <h2 class="painel-titulo">Lista de afiliados</h2>
        </div>
        <?php if ($afiliados): ?>
        <table>
          <thead>
            <tr>
              <th>Nome</th>
              <th>E-mail</th>
              <th>Código</th>
              <th>Comissão</th>
              <th>Indicações</th>
              <th>Comissões acumuladas</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($afiliados as $a): ?>
            <tr>
              <td style="color:var(--ink);font-weight:600;"><?= htmlspecialchars($a['nome']) ?></td>
              <td><?= htmlspecialchars($a['email']) ?></td>
              <td><?= htmlspecialchars($a['codigo']) ?></td>
              <td><?= htmlspecialchars($a['comissao_percentual']) ?>%</td>
              <td><?= contaIndicacoesPorAfiliado($a['id_afiliado']) ?></td>
              <td>$<?= number_format(somaComissoesPorAfiliado($a['id_afiliado']), 2) ?></td>
              <td><span class="selo <?= $a['ativo'] ? 'verde' : 'vermelho' ?>"><span class="ponto"></span> <?= $a['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="estado-vazio">Nenhum afiliado cadastrado ainda.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div id="modal-criar" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('aberto')">
  <div class="modal">
    <div class="modal-header">
      <h2>Novo afiliado</h2>
      <button type="button" class="modal-fechar" onclick="this.closest('.modal-overlay').classList.remove('aberto')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="post" action="afiliados.php">
      <input type="hidden" name="acao" value="criar" />
      <div class="modal-body">
        <div class="campo">
          <label>Nome</label>
          <div class="campo-entrada"><input type="text" name="nome" required /></div>
        </div>
        <div class="campo">
          <label>E-mail</label>
          <div class="campo-entrada"><input type="email" name="email" required /></div>
        </div>
        <div class="campo">
          <label>Senha</label>
          <div class="campo-entrada"><input type="password" name="senha" required minlength="6" /></div>
        </div>
        <div class="campo">
          <label>Comissão (%)</label>
          <div class="campo-entrada"><input type="number" name="comissao_percentual" value="10" min="0" max="100" step="0.01" required /></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="botao-pequeno botao-fantasma" onclick="this.closest('.modal-overlay').classList.remove('aberto')">Cancelar</button>
        <button type="submit" class="botao-pequeno botao-primario-pequeno">Criar afiliado</button>
      </div>
    </form>
  </div>
</div>

<?php if ($msg_erro): ?>
<script>document.getElementById('modal-criar').classList.add('aberto');</script>
<?php endif; ?>

</body>
</html>
