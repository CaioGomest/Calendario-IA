<?php
require_once __DIR__ . '/funcoes/funcoesIdioma.php';
?>
<!DOCTYPE html>
<html lang="<?= traduz('lang_code') ?>">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= traduz('priv_titulo') ?> — <?= htmlspecialchars(nomeApp()) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="assets/css/landing.css" />
<link rel="stylesheet" href="assets/css/legal.css" />
</head>
<body>

<nav class="barra-nav">
  <a class="marca" href="landpage.php">
    <span class="logo"><span data-bot="ink" data-size="24"></span></span>
    <?= htmlspecialchars(nomeApp()) ?>
  </a>
  <a href="cliente/login.php" class="entrar"><?= traduz('lp_login') ?></a>
</nav>

<main class="legal-container">
  <h1><?= traduz('priv_titulo') ?></h1>
  <p class="legal-atualizado"><?= traduz('priv_atualizado') ?></p>

  <section>
    <h2>1. <?= traduz('priv_s1_titulo') ?></h2>
    <p><?= traduz('priv_s1_texto') ?></p>
  </section>

  <section>
    <h2>2. <?= traduz('priv_s2_titulo') ?></h2>
    <p><?= traduz('priv_s2_texto') ?></p>
    <ul>
      <li><?= traduz('priv_s2_item1') ?></li>
      <li><?= traduz('priv_s2_item2') ?></li>
      <li><?= traduz('priv_s2_item3') ?></li>
      <li><?= traduz('priv_s2_item4') ?></li>
      <li><?= traduz('priv_s2_item5') ?></li>
    </ul>
  </section>

  <section>
    <h2>3. <?= traduz('priv_s3_titulo') ?></h2>
    <p><?= traduz('priv_s3_texto') ?></p>
    <ul>
      <li><?= traduz('priv_s3_item1') ?></li>
      <li><?= traduz('priv_s3_item2') ?></li>
      <li><?= traduz('priv_s3_item3') ?></li>
      <li><?= traduz('priv_s3_item4') ?></li>
    </ul>
  </section>

  <section>
    <h2>4. <?= traduz('priv_s4_titulo') ?></h2>
    <p><?= traduz('priv_s4_texto') ?></p>
  </section>

  <section>
    <h2>5. <?= traduz('priv_s5_titulo') ?></h2>
    <p><?= traduz('priv_s5_texto') ?></p>
    <ul>
      <li><?= traduz('priv_s5_item1') ?></li>
      <li><?= traduz('priv_s5_item2') ?></li>
      <li><?= traduz('priv_s5_item3') ?></li>
      <li><?= traduz('priv_s5_item4') ?></li>
      <li><?= traduz('priv_s5_item5') ?></li>
      <li><?= traduz('priv_s5_item6') ?></li>
    </ul>
  </section>

  <section>
    <h2>6. <?= traduz('priv_s6_titulo') ?></h2>
    <p><?= traduz('priv_s6_texto') ?></p>
  </section>

  <section>
    <h2>7. <?= traduz('priv_s7_titulo') ?></h2>
    <p><?= traduz('priv_s7_texto') ?></p>
  </section>

  <section>
    <h2>8. <?= traduz('priv_s8_titulo') ?></h2>
    <p><?= traduz('priv_s8_texto') ?></p>
  </section>

  <section>
    <h2>9. <?= traduz('priv_s9_titulo') ?></h2>
    <p><?= traduz('priv_s9_texto') ?></p>
  </section>

  <section>
    <h2>10. <?= traduz('priv_s10_titulo') ?></h2>
    <p><?= traduz('priv_s10_texto') ?></p>
  </section>
</main>

<footer class="rodape-landing">
  <div class="marca-rodape">
    <span class="logo"><span data-bot="white" data-size="20"></span></span>
    <?= htmlspecialchars(nomeApp()) ?>
  </div>
  <div class="links-rodape">
    <a href="privacidade.php"><?= traduz('lp_privacy') ?></a>
    <a href="termos.php"><?= traduz('lp_terms') ?></a>
  </div>
  <div class="copyright"><?= sprintf(traduz('lp_footer_copy'), date('Y')) ?></div>
</footer>

<script src="assets/js/mascote.js"></script>
</body>
</html>
