<?php
$inicial_sidebar_afiliado = mb_strtoupper(mb_substr($afiliado['nome'], 0, 1));
?>
<aside class="sidebar">
  <div class="sidebar-marca"><span class="logo"><span data-bot="ink" data-size="22"></span></span> <?= htmlspecialchars(nomeApp()) ?></div>
  <a class="nav-link <?= $pagina_atual === 'painel' ? 'ativo' : '' ?>" href="painel.php"><span class="nav-icone">📊</span> Painel</a>
  <a class="nav-link <?= $pagina_atual === 'link' ? 'ativo' : '' ?>" href="link.php"><span class="nav-icone">🔗</span> Meu link</a>
  <a class="nav-link <?= $pagina_atual === 'comissoes' ? 'ativo' : '' ?>" href="comissoes.php"><span class="nav-icone">💰</span> Comissões</a>
  <a class="nav-link <?= $pagina_atual === 'saque' ? 'ativo' : '' ?>" href="saque.php"><span class="nav-icone">🏦</span> Saques</a>
  <div class="sidebar-perfil">
    <span class="perfil-avatar"><?= htmlspecialchars($inicial_sidebar_afiliado) ?></span>
    <div class="perfil-info"><b><?= htmlspecialchars($afiliado['nome']) ?></b><span><?= htmlspecialchars($afiliado['email']) ?></span></div>
    <a class="perfil-logout" href="logout.php" title="Sair">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
        <polyline points="16 17 21 12 16 7" />
        <line x1="21" y1="12" x2="9" y2="12" />
      </svg>
    </a>
  </div>
</aside>
