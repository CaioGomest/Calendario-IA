<nav class="barra-abas">
  <a class="<?= $pagina_atual === 'painel' ? 'ativo' : '' ?>" href="painel"><span class="aba-icone">📊</span> Painel</a>
  <a class="<?= $pagina_atual === 'link' ? 'ativo' : '' ?>" href="link"><span class="aba-icone">🔗</span> Link</a>
  <a class="<?= $pagina_atual === 'comissoes' ? 'ativo' : '' ?>" href="comissoes"><span class="aba-icone">💰</span> Comissões</a>
  <a class="<?= $pagina_atual === 'saque' ? 'ativo' : '' ?>" href="saque"><span class="aba-icone">🏦</span> Saques</a>
</nav>
