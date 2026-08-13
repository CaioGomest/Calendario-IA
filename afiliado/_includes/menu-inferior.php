<nav class="barra-abas">
  <a class="<?= $pagina_atual === 'painel' ? 'ativo' : '' ?>" href="painel.php"><span class="aba-icone">📊</span> Painel</a>
  <a class="<?= $pagina_atual === 'link' ? 'ativo' : '' ?>" href="link.php"><span class="aba-icone">🔗</span> Link</a>
  <a class="<?= $pagina_atual === 'comissoes' ? 'ativo' : '' ?>" href="comissoes.php"><span class="aba-icone">💰</span> Comissões</a>
  <a class="<?= $pagina_atual === 'saque' ? 'ativo' : '' ?>" href="saque.php"><span class="aba-icone">🏦</span> Saques</a>
</nav>
