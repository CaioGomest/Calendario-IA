<?php
require_once __DIR__ . '/funcoes/funcoesIdioma.php';
require_once __DIR__ . '/funcoes/funcoesPlanos.php';

$planos_ativos = listaPlanos(['ativo' => 1]);
$moeda = traduz('lp_moeda');

$sufixo_ciclo = [
    'mensal' => traduz('lp_ciclo_mensal'),
    'trimestral' => traduz('lp_ciclo_trimestral'),
    'anual' => traduz('lp_ciclo_anual'),
];
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= traduz('lp_title') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="assets/css/landing.css" />
</head>
<body>

<nav class="barra-nav">
  <a class="marca" href="landpage.php">
    <span class="logo"><span data-bot="ink" data-size="24"></span></span>
    CalendarioIA
  </a>
  <a class="entrar" href="cliente/login.php"><?= traduz('lp_login') ?></a>
</nav>

<!-- HERO -->
<section class="destaque">
  <div class="destaque-curva">
    <div class="adesivo mascote-flutua acenando"><span data-bot="ink" data-size="66"></span></div>
    <h1 class="titulo-fonte revelar"><?= traduz('lp_hero_h1') ?> <span class="cal">🗓️</span></h1>
    <p class="subtexto revelar d1"><?= traduz('lp_hero_lead') ?></p>
  </div>
  <div class="destaque-acoes">
    <a href="cliente/cadastro.php" class="botao botao-primario revelar d1"><?= traduz('lp_hero_cta') ?></a>
    <div class="micro revelar d2"><?= traduz('lp_hero_micro') ?></div>
    <div class="social revelar d3">
      <span class="avatares">
        <i style="background:#f4b740"></i>
        <i style="background:#5187e8"></i>
        <i style="background:#2f9e44"></i>
      </span>
      <?= traduz('lp_hero_social') ?>
    </div>
  </div>
</section>

<!-- COMO FUNCIONA -->
<section class="secao-passos centralizado">
  <span class="etiqueta-secao revelar"><?= traduz('lp_how_eyebrow') ?></span>
  <h2 class="titulo-secao titulo-fonte revelar d1"><?= traduz('lp_how_title') ?></h2>
  <p class="subtitulo-secao revelar d1"><?= traduz('lp_how_sub') ?></p>
  <div class="passos">
    <div class="passo revelar d1">
      <div class="numero">1</div>
      <div><h3><?= traduz('lp_step1_title') ?></h3><p><?= traduz('lp_step1_text') ?></p></div>
    </div>
    <div class="passo revelar d2">
      <div class="numero">2</div>
      <div><h3><?= traduz('lp_step2_title') ?></h3><p><?= traduz('lp_step2_text') ?></p></div>
    </div>
    <div class="passo revelar d3">
      <div class="numero">3</div>
      <div><h3><?= traduz('lp_step3_title') ?></h3><p><?= traduz('lp_step3_text') ?></p></div>
    </div>
  </div>
</section>

<!-- DEMO -->
<section class="secao-demo centralizado">
  <span class="etiqueta-secao revelar"><?= traduz('lp_demo_eyebrow') ?></span>
  <h2 class="titulo-secao titulo-fonte revelar d1"><?= traduz('lp_demo_title') ?></h2>
  <p class="subtitulo-secao revelar d1"><?= traduz('lp_demo_sub') ?></p>
  <div class="demo revelar d1" id="demo">
    <div class="demo-barra">
      <span class="seta-voltar"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></span>
      <span class="avatar"><span data-bot="white" data-size="24"></span></span>
      <span class="quem"><b>CalendarioIA</b><span><?= traduz('lp_demo_online') ?></span></span>
      <span class="icones-wa">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.68 2.36a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.76.32 1.55.55 2.36.68A2 2 0 0 1 22 16.92z"/></svg>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
      </span>
    </div>
    <div class="conversa" id="conversa"></div>
    <div class="demo-barra-input">
      <div class="input-falso">
        <span class="emoji">😊</span>
        <span>Mensaje</span>
      </div>
      <span class="botao-mic"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5-3c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg></span>
    </div>
  </div>
  <div class="demo-nota revelar d1"><?= traduz('lp_demo_note') ?></div>
</section>

<!-- BENEFÍCIOS -->
<section class="secao-beneficios centralizado">
  <span class="etiqueta-secao revelar"><?= traduz('lp_ben_eyebrow') ?></span>
  <h2 class="titulo-secao titulo-fonte revelar d1"><?= traduz('lp_ben_title') ?></h2>
  <div class="grade">
    <div class="beneficio revelar d1"><span class="icone">📱</span><b><?= traduz('lp_ben1_title') ?></b><span><?= traduz('lp_ben1_text') ?></span></div>
    <div class="beneficio revelar d1"><span class="icone">🗓️</span><b><?= traduz('lp_ben2_title') ?></b><span><?= traduz('lp_ben2_text') ?></span></div>
    <div class="beneficio revelar d2"><span class="icone">⏰</span><b><?= traduz('lp_ben3_title') ?></b><span><?= traduz('lp_ben3_text') ?></span></div>
    <div class="beneficio revelar d2"><span class="icone">🌙</span><b><?= traduz('lp_ben4_title') ?></b><span><?= traduz('lp_ben4_text') ?></span></div>
    <div class="beneficio revelar d3"><span class="icone">🔒</span><b><?= traduz('lp_ben5_title') ?></b><span><?= traduz('lp_ben5_text') ?></span></div>
    <div class="beneficio revelar d3"><span class="icone"><?= traduz('lp_ben6_flag') ?></span><b><?= traduz('lp_ben6_title') ?></b><span><?= traduz('lp_ben6_text') ?></span></div>
  </div>
</section>

<?php if ($planos_ativos): ?>
<!-- PREÇO -->
<section class="secao-preco centralizado">
  <span class="etiqueta-secao revelar">
    <?= count($planos_ativos) > 1 ? traduz('lp_price_eyebrow_multi') : traduz('lp_price_eyebrow_single') ?>
  </span>
  <h2 class="titulo-secao titulo-fonte revelar d1"><?= traduz('lp_price_title') ?></h2>

  <div class="grade-planos">
    <?php foreach ($planos_ativos as $plano): ?>
    <?php
      $cor_mapa = [
          'amarelo' => 'background:var(--gold);color:#5a3d00;box-shadow:0 8px 16px -8px rgba(244,183,64,.9)',
          'azul' => 'background:#3b82f6;color:#fff;box-shadow:0 8px 16px -8px rgba(59,130,246,.6)',
          'verde' => 'background:#22c55e;color:#fff;box-shadow:0 8px 16px -8px rgba(34,197,94,.6)',
          'vermelho' => 'background:#ef4444;color:#fff;box-shadow:0 8px 16px -8px rgba(239,68,68,.6)',
          'roxo' => 'background:#a855f7;color:#fff;box-shadow:0 8px 16px -8px rgba(168,85,247,.6)',
      ];
      $cor_etiqueta = $plano['etiqueta_cor'] ?? 'amarelo';
      $estilo_etiqueta = $cor_mapa[$cor_etiqueta] ?? $cor_mapa['amarelo'];
      $texto_etiqueta = trim($plano['etiqueta_texto'] ?? '');
    ?>
    <div class="plano revelar d1">
      <?php if ($texto_etiqueta): ?>
      <div class="etiqueta" style="<?= $estilo_etiqueta ?>"><?= htmlspecialchars($texto_etiqueta) ?></div>
      <?php endif; ?>
      <div class="nome-plano"><?= htmlspecialchars($plano['nome']) ?></div>
      <div class="montante">
        <span class="moeda"><?= $moeda ?></span>
        <span class="valor"><?= (int)$plano['preco'] ?></span>
        <span class="periodo"><?= $sufixo_ciclo[$plano['ciclo']] ?? $sufixo_ciclo['mensal'] ?></span>
      </div>
      <?php if ($plano['descricao']): ?>
      <div class="anterior"><?= htmlspecialchars($plano['descricao']) ?></div>
      <?php endif; ?>
      <div class="inclusos">
        <div><span class="check">✓</span> <?= traduz('lp_feat1') ?></div>
        <div><span class="check">✓</span> <?= traduz('lp_feat2') ?></div>
        <div><span class="check">✓</span> <?= traduz('lp_feat3') ?></div>
        <div><span class="check">✓</span> <?= traduz('lp_feat4') ?></div>
        <div><span class="check">✓</span> <?= traduz('lp_feat5') ?></div>
      </div>
      <?php if ((int)$plano['dias_teste'] > 0): ?>
      <a href="cliente/cadastro.php" class="botao botao-primario"><?= traduz('lp_price_cta') ?></a>
      <?php else: ?>
      <a href="cliente/cadastro.php" class="botao botao-primario"><?= traduz('lp_price_cta_sem_teste') ?></a>
      <?php endif; ?>
      <?php if ((int)$plano['dias_teste'] > 0): ?>
      <div class="garantia"><?= sprintf(traduz('lp_price_guarantee'), (int)$plano['dias_teste']) ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- FAQ -->
<section class="secao-faq centralizado">
  <span class="etiqueta-secao revelar"><?= traduz('lp_faq_eyebrow') ?></span>
  <h2 class="titulo-secao titulo-fonte revelar d1"><?= traduz('lp_faq_title') ?></h2>
  <div class="faq">
    <div class="pergunta revelar d1">
      <button><?= traduz('lp_faq1_q') ?><span class="mais-menos">+</span></button>
      <div class="resposta"><p><?= traduz('lp_faq1_a') ?></p></div>
    </div>
    <div class="pergunta revelar d1">
      <button><?= traduz('lp_faq2_q') ?><span class="mais-menos">+</span></button>
      <div class="resposta"><p><?= traduz('lp_faq2_a') ?></p></div>
    </div>
    <div class="pergunta revelar d2">
      <button><?= traduz('lp_faq3_q') ?><span class="mais-menos">+</span></button>
      <div class="resposta"><p><?= traduz('lp_faq3_a') ?></p></div>
    </div>
    <div class="pergunta revelar d2">
      <button><?= traduz('lp_faq4_q') ?><span class="mais-menos">+</span></button>
      <div class="resposta"><p><?= traduz('lp_faq4_a') ?></p></div>
    </div>
    <div class="pergunta revelar d3">
      <button><?= traduz('lp_faq5_q') ?><span class="mais-menos">+</span></button>
      <div class="resposta"><p><?= traduz('lp_faq5_a') ?></p></div>
    </div>
  </div>
</section>

<section class="secao-cta centralizado">
  <div class="adesivo mascote-flutua acenando"><span data-bot="ink" data-size="54"></span></div>
  <h2 class="titulo-fonte revelar"><?= traduz('lp_final_title') ?></h2>
  <p class="revelar d1"><?= traduz('lp_final_text') ?></p>
  <a href="cliente/cadastro.php" class="botao botao-cta-branco revelar d1"><?= traduz('lp_final_cta') ?></a>
  <div class="micro-rodape revelar d2"><?= traduz('lp_final_micro') ?></div>
</section>

<footer class="rodape-landing">
  <div class="marca-rodape">
    <span class="logo"><span data-bot="white" data-size="20"></span></span>
    CalendarioIA
  </div>
  <div class="links-rodape">
    <a href="privacidade.php"><?= traduz('lp_privacy') ?></a>
    <a href="termos.php"><?= traduz('lp_terms') ?></a>
    <a href="#"><?= traduz('lp_support') ?></a>
    <a href="#"><?= traduz('lp_contact') ?></a>
  </div>
  <div class="copyright"><?= sprintf(traduz('lp_footer_copy'), date('Y')) ?></div>
</footer>

<script src="assets/js/mascote.js"></script>
<script>
(function(){
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('visivel'); io.unobserve(e.target); } });
  },{threshold:.16, rootMargin:'0px 0px -8% 0px'});
  document.querySelectorAll('.revelar').forEach(function(el){ io.observe(el); });

  document.querySelectorAll('.pergunta button').forEach(function(btn){
    btn.addEventListener('click', function(){
      var qa = btn.closest('.pergunta');
      var ans = qa.querySelector('.resposta');
      var open = qa.classList.contains('aberto');
      document.querySelectorAll('.pergunta.aberto').forEach(function(o){ o.classList.remove('aberto'); o.querySelector('.resposta').style.maxHeight = null; });
      if(!open){ qa.classList.add('aberto'); ans.style.maxHeight = ans.scrollHeight + 'px'; }
    });
  });

  var THREAD = document.getElementById('conversa');
  var checks = '<span class="verificado"><svg width="16" height="11" viewBox="0 0 16 11"><path d="M11.07.66L5.4 6.33 3.14 4.07l-1.06 1.06 3.32 3.32 6.73-6.73z" fill="currentColor"/><path d="M14.07.66L8.4 6.33 7.57 5.5l-1.06 1.06 1.89 1.89 6.73-6.73z" fill="currentColor"/></svg></span>';
  var SCRIPT = [
    {who:'eu',  text:<?= json_encode(traduz('lp_demo_me1')) ?>, time:'10:32'},
    {who:'digitando'},
    {who:'ele',text:<?= json_encode(traduz('lp_demo_them1')) ?>, time:<?= json_encode(traduz('lp_demo_stamp1')) ?>},
    {who:'eu',  text:<?= json_encode(traduz('lp_demo_me2')) ?>, time:'10:33'},
    {who:'digitando'},
    {who:'ele',text:<?= json_encode(traduz('lp_demo_them2')) ?>, time:<?= json_encode(traduz('lp_demo_stamp2')) ?>}
  ];
  function el(cls){ var d=document.createElement('div'); d.className=cls; return d; }
  function addBubble(item){
    var b = el('balao '+item.who);
    var meta = '<span class="balao-info">' + (item.time||'') + (item.who==='eu' ? ' '+checks : '') + '</span>';
    var name = item.who==='ele' ? '<span class="nome-contato">CalendarioIA</span>' : '';
    b.innerHTML = name + '<span class="balao-interno">' + item.text + '</span>' + meta;
    THREAD.appendChild(b);
    void b.offsetWidth;
    setTimeout(function(){ b.classList.add('visivel'); }, 20);
    return b;
  }
  function addTyping(){
    var t = el('digitando'); t.innerHTML='<i></i><i></i><i></i>'; THREAD.appendChild(t); return t;
  }
  var played=false;
  function play(){
    if(played) return; played=true;
    var i=0;
    (function next(){
      if(i>=SCRIPT.length) return;
      var item=SCRIPT[i++];
      if(item.who==='digitando'){ var t=addTyping(); setTimeout(function(){ t.remove(); next(); }, 900); }
      else { addBubble(item); setTimeout(next, item.who==='eu'?650:1100); }
    })();
  }
  var demoEl = document.getElementById('demo');
  var demoIO = new IntersectionObserver(function(entries){
    entries.forEach(function(e){ if(e.isIntersecting){ play(); demoIO.disconnect(); } });
  },{threshold:0, rootMargin:'0px 0px -20% 0px'});
  demoIO.observe(demoEl);
  (function checkNow(){
    var r=demoEl.getBoundingClientRect();
    if(r.top < window.innerHeight*0.85 && r.bottom > 0){ play(); }
  })();
})();
</script>
</body>
</html>
