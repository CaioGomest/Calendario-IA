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
  </div>
</aside>
