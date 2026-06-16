<?php
// este include usa $usuario (dados do usuário logado) e $pagina_atual ('home' ou 'conta')
// quem faz o require precisa definir as duas antes, como conta.php e home.php fazem
$inicial_sidebar = mb_strtoupper(mb_substr($usuario['nome'], 0, 1));
?>
<aside class="sidebar">
  <div class="sidebar-marca"><span class="logo"><span data-bot="ink" data-size="22"></span></span> CalendarioIA</div>
  <a class="nav-link <?= $pagina_atual === 'home' ? 'ativo' : '' ?>" href="home.php"><span class="nav-icone">🏠</span> <?= traduz('menu_inicio') ?></a>
  <a class="nav-link <?= $pagina_atual === 'conta' ? 'ativo' : '' ?>" href="conta.php"><span class="nav-icone">👤</span> <?= traduz('menu_cuenta') ?></a>
  <a class="nav-link" href="https://wa.me/" target="_blank"><span class="nav-icone">💬</span> <?= traduz('menu_whatsapp') ?></a>
  <div class="sidebar-plano">
    <b><?= traduz('upgrade_titulo') ?></b>
    <span><?= traduz('upgrade_texto') ?></span>
    <button class="botao" type="button"><?= traduz('upgrade_botao') ?></button>
  </div>
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
