<?php
// este include usa a$pagina_atual ('home' ou 'conta')
// quem faz o require precisa definir como conta.php e home.php fazem
?>
<nav class="barra-abas">
  <a class="<?= $pagina_atual === 'home' ? 'ativo' : '' ?>" href="home.php"><span class="aba-icone">🏠</span> <?= traduz('menu_inicio') ?></a>
  <a class="<?= $pagina_atual === 'conta' ? 'ativo' : '' ?>" href="conta.php"><span class="aba-icone">👤</span> <?= traduz('menu_cuenta') ?></a>
</nav>
