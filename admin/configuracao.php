<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'configuracao';

$variaveis = [
    ['nome' => 'APP_ENV', 'valor' => APP_ENV, 'descricao' => 'Ambiente atual da aplicação'],
    ['nome' => 'DB_HOST', 'valor' => DB_HOST, 'descricao' => 'Host do banco de dados'],
    ['nome' => 'DB_NAME', 'valor' => DB_NAME, 'descricao' => 'Nome do banco de dados'],
    ['nome' => 'INTERNAL_SECRET', 'valor' => INTERNAL_SECRET ? str_repeat('•', 6) . substr(INTERNAL_SECRET, -4) : '(vazio)', 'descricao' => 'Secret dos endpoints internos'],
    ['nome' => 'IDIOMA_PADRAO', 'valor' => IDIOMA_PADRAO, 'descricao' => 'Idioma padrão do sistema'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — Configuração</title>
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
        <h1>Configuração</h1>
        <div class="subtitulo">Variáveis de ambiente e estado do sistema</div>
      </div>
    </header>
    <div class="admin-area">
      <div class="config-grid">

        <div class="config-card">
          <div class="config-card-header">
            <span class="header-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            </span>
            <h2>Variáveis de ambiente</h2>
          </div>
          <?php foreach ($variaveis as $v): ?>
          <div class="config-item">
            <div class="config-item-info">
              <b><?= htmlspecialchars($v['nome']) ?></b>
              <span><?= htmlspecialchars($v['descricao']) ?></span>
            </div>
            <span class="config-value"><?= htmlspecialchars($v['valor']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="config-card">
          <div class="config-card-header">
            <span class="header-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
            </span>
            <h2>Sistema</h2>
          </div>
          <div class="config-item">
            <div class="config-item-info">
              <b>PHP</b>
              <span>Versão do interpretador</span>
            </div>
            <span class="config-value"><?= phpversion() ?></span>
          </div>
          <div class="config-item">
            <div class="config-item-info">
              <b>Servidor</b>
              <span>Software do servidor web</span>
            </div>
            <span class="config-value"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? '—') ?></span>
          </div>
          <div class="config-item">
            <div class="config-item-info">
              <b>Modo dev</b>
              <span>Simulações e bypass ativados</span>
            </div>
            <span class="badge <?= MODO_DEV ? 'amber' : 'green' ?>"><?= MODO_DEV ? 'Ativado' : 'Desativado' ?></span>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</body>
</html>
