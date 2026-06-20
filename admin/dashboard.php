<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesEventos.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'dashboard';

$total_usuarios = contaTotalUsuarios();
$usuarios_ativos = contaUsuariosAtivos();
$por_plano = contaUsuariosPorPlano();
$total_eventos = contaTotalEventos();
$eventos_hoje = contaEventosHoje();
$total_mensagens = contaTotalMensagens();
$eventos_recentes = listaEventosRecentes(8);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — Dashboard</title>
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
        <h1>Dashboard</h1>
        <div class="subtitulo">Visão geral do sistema</div>
      </div>
    </header>
    <div class="admin-area">

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Total de usuários</span>
            <span class="stat-icon blue">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
          </div>
          <div class="stat-value"><?= $total_usuarios ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Usuários ativos</span>
            <span class="stat-icon green">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </span>
          </div>
          <div class="stat-value"><?= $usuarios_ativos ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Eventos criados</span>
            <span class="stat-icon purple">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
          </div>
          <div class="stat-value"><?= $total_eventos ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Mensagens trocadas</span>
            <span class="stat-icon amber">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
          </div>
          <div class="stat-value"><?= $total_mensagens ?></div>
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Em trial</span>
            <span class="stat-icon amber">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
          </div>
          <div class="stat-value"><?= $por_plano['trial'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Plano ativo</span>
            <span class="stat-icon green">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </span>
          </div>
          <div class="stat-value"><?= $por_plano['ativo'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Cancelados</span>
            <span class="stat-icon red">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </span>
          </div>
          <div class="stat-value"><?= $por_plano['cancelado'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Eventos hoje</span>
            <span class="stat-icon blue">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
            </span>
          </div>
          <div class="stat-value"><?= $eventos_hoje ?></div>
        </div>
      </div>

      <div class="section-gap">
        <div class="panel">
          <div class="panel-header">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-4)"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <h2 class="panel-title">Eventos recentes</h2>
          </div>
          <?php if ($eventos_recentes): ?>
          <table>
            <thead>
              <tr>
                <th>Título</th>
                <th>Usuário</th>
                <th>Data</th>
                <th>Lembrete</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($eventos_recentes as $ev): ?>
              <tr>
                <td style="color:var(--ink);font-weight:600;"><?= htmlspecialchars($ev['titulo']) ?></td>
                <td><?= htmlspecialchars($ev['nome_usuario']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($ev['data_inicio'])) ?></td>
                <td><span class="badge <?= $ev['lembrete_enviado'] ? 'green' : 'amber' ?>"><span class="dot"></span> <?= $ev['lembrete_enviado'] ? 'Enviado' : 'Pendente' ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            Nenhum evento registrado ainda.
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
