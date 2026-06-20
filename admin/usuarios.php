<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'usuarios';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $id = (int) ($_POST['id_usuario'] ?? 0);
    if ($_POST['acao'] === 'ativar') {
        atualizaAtivoUsuario($id, true);
    } elseif ($_POST['acao'] === 'desativar') {
        atualizaAtivoUsuario($id, false);
    }
    header('Location: usuarios.php?' . http_build_query(array_filter([
        'busca' => $_GET['busca'] ?? '',
        'plano' => $_GET['plano'] ?? '',
    ])));
    exit;
}

$filtro = [];
if (!empty($_GET['busca'])) {
    $filtro['busca'] = $_GET['busca'];
}
if (!empty($_GET['plano'])) {
    $filtro['plano'] = $_GET['plano'];
}

$usuarios = listaUsuarios($filtro);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — Usuários</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/admin.css" />
</head>
<body>
<div class="admin-estrutura">
  <?php require __DIR__ . '/_includes/sidebar.php'; ?>
  <div class="admin-conteudo">
    <header class="admin-barra">
      <div>
        <h1>Usuários</h1>
        <div class="subtitulo"><?= count($usuarios) ?> usuário<?= count($usuarios) !== 1 ? 's' : '' ?> encontrado<?= count($usuarios) !== 1 ? 's' : '' ?></div>
      </div>
    </header>
    <div class="admin-area">
      <div class="panel">
        <div class="panel-header">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-4)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          <h2 class="panel-title">Lista de usuários</h2>
          <div class="spacer"></div>
          <form method="get" action="usuarios.php" style="display:flex;gap:8px;align-items:center;">
            <div class="search-box">
              <span class="icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
              <input type="text" name="busca" placeholder="Buscar por nome, e-mail..." value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>" />
            </div>
            <select name="plano" class="filter-select" onchange="this.form.submit()">
              <option value="">Todos os planos</option>
              <option value="trial" <?= ($_GET['plano'] ?? '') === 'trial' ? 'selected' : '' ?>>Trial</option>
              <option value="ativo" <?= ($_GET['plano'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
              <option value="cancelado" <?= ($_GET['plano'] ?? '') === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
            </select>
          </form>
        </div>
        <?php if ($usuarios): ?>
        <table>
          <thead>
            <tr>
              <th>Nome</th>
              <th>E-mail</th>
              <th>Telefone</th>
              <th>Plano</th>
              <th>Status</th>
              <th>Cadastro</th>
              <th style="text-align:right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
              <td style="color:var(--ink);font-weight:600;"><?= htmlspecialchars($u['nome']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['telefone'] ?: '—') ?></td>
              <td>
                <?php
                  $cor_plano = match($u['plano']) {
                      'trial' => 'amber',
                      'ativo' => 'green',
                      'cancelado' => 'red',
                      default => 'muted',
                  };
                ?>
                <span class="badge <?= $cor_plano ?>"><?= htmlspecialchars(ucfirst($u['plano'])) ?></span>
              </td>
              <td><span class="badge <?= $u['ativo'] ? 'green' : 'red' ?>"><span class="dot"></span> <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
              <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
              <td style="text-align:right;">
                <form method="post" action="usuarios.php?<?= http_build_query(array_filter(['busca' => $_GET['busca'] ?? '', 'plano' => $_GET['plano'] ?? ''])) ?>" style="display:inline;">
                  <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>" />
                  <?php if ($u['ativo']): ?>
                    <button type="submit" name="acao" value="desativar" class="action-btn danger">Desativar</button>
                  <?php else: ?>
                    <button type="submit" name="acao" value="ativar" class="action-btn">Ativar</button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          Nenhum usuário encontrado.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
