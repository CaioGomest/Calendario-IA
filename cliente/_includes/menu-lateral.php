<?php
require_once __DIR__ . '/../../funcoes/funcoesPlanos.php';
$inicial_sidebar = mb_strtoupper(mb_substr($usuario['nome'], 0, 1));
$agora_sidebar = new DateTime();
$sem_plano = empty($usuario['plano']) || $usuario['plano'] === 'cancelado';
$expirado_sidebar = !empty($usuario['plano_expira_em']) && new DateTime($usuario['plano_expira_em']) < $agora_sidebar;
$mostrar_upgrade = $sem_plano || $expirado_sidebar;
$plano_sugerido = $mostrar_upgrade ? buscaPlanoSugerido() : null;

$sufixo_ciclo_sidebar = [
    'mensal' => traduz('upgrade_ciclo_mensal'),
    'trimestral' => traduz('upgrade_ciclo_trimestral'),
    'anual' => traduz('upgrade_ciclo_anual'),
];
?>
<aside class="sidebar">
  <div class="sidebar-marca"><span class="logo"><span data-bot="ink" data-size="22"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
  <a class="nav-link <?= $pagina_atual === 'home' ? 'ativo' : '' ?>" href="home.php"><span class="nav-icone">🏠</span> <?= traduz('menu_inicio') ?></a>
  <a class="nav-link <?= $pagina_atual === 'financas' ? 'ativo' : '' ?>" href="financas.php"><span class="nav-icone">💰</span> <?= traduz('menu_financas') ?></a>
  <a class="nav-link <?= $pagina_atual === 'conta' ? 'ativo' : '' ?>" href="conta.php"><span class="nav-icone">👤</span> <?= traduz('menu_cuenta') ?></a>
  <a class="nav-link" href="https://wa.me/" target="_blank"><span class="nav-icone">💬</span> <?= traduz('menu_whatsapp') ?></a>
  <?php if ($plano_sugerido): ?>
  <div class="sidebar-plano">
    <b><?= htmlspecialchars($plano_sugerido['nome']) ?></b>
    <span><?= simboloMoeda() ?><?= number_format((float)$plano_sugerido['preco'], 0) ?> <?= $sufixo_ciclo_sidebar[$plano_sugerido['ciclo']] ?? '' ?></span>
    <a class="botao" href="conta.php"><?= traduz('upgrade_botao') ?></a>
  </div>
  <?php endif; ?>
  <div class="sidebar-perfil">
    <span class="perfil-avatar"><?= htmlspecialchars($inicial_sidebar) ?></span>
    <div class="perfil-info"><b><?= htmlspecialchars($usuario['nome']) ?></b><span><?= htmlspecialchars($usuario['email']) ?></span></div>
    <a class="perfil-logout" href="logout.php" title="<?= traduz('menu_salir') ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
        <polyline points="16 17 21 12 16 7" />
        <line x1="21" y1="12" x2="9" y2="12" />
      </svg>
    </a>
  </div>
</aside>
