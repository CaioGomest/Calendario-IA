<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesPlanos.php';
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'planos';
$msg_sucesso = '';
$msg_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $id = (int) ($_POST['id_plano'] ?? 0);

    if ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $ciclo = $_POST['ciclo'] ?? 'mensal';
        $preco = (float) ($_POST['preco'] ?? 0);
        $dias_teste = (int) ($_POST['dias_teste'] ?? 0);
        $etiqueta_texto = trim($_POST['etiqueta_texto'] ?? '');
        $etiqueta_cor = $_POST['etiqueta_cor'] ?? 'amarelo';

        if (!$nome || $preco <= 0) {
            $msg_erro = traduz('admin_erro_nome_preco');
        } else {
            inserePlano([
                'nome' => $nome,
                'descricao' => $descricao,
                'ciclo' => $ciclo,
                'preco' => $preco,
                'dias_teste' => $dias_teste,
                'etiqueta_texto' => $etiqueta_texto,
                'etiqueta_cor' => $etiqueta_cor,
            ]);
            $msg_sucesso = traduz('admin_plano_criado');
        }
    } elseif ($acao === 'editar') {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $ciclo = $_POST['ciclo'] ?? 'mensal';
        $preco = (float) ($_POST['preco'] ?? 0);
        $dias_teste = (int) ($_POST['dias_teste'] ?? 0);
        $etiqueta_texto = trim($_POST['etiqueta_texto'] ?? '');
        $etiqueta_cor = $_POST['etiqueta_cor'] ?? 'amarelo';

        if (!$nome || $preco <= 0) {
            $msg_erro = traduz('admin_erro_nome_preco');
        } else {
            atualizaPlano($id, [
                'nome' => $nome,
                'descricao' => $descricao,
                'ciclo' => $ciclo,
                'preco' => $preco,
                'dias_teste' => $dias_teste,
                'etiqueta_texto' => $etiqueta_texto,
                'etiqueta_cor' => $etiqueta_cor,
            ]);
            $msg_sucesso = traduz('admin_plano_atualizado');
        }
    } elseif ($acao === 'ativar') {
        atualizaAtivoPlano($id, true);
        $msg_sucesso = traduz('admin_plano_ativado');
    } elseif ($acao === 'desativar') {
        atualizaAtivoPlano($id, false);
        $msg_sucesso = traduz('admin_plano_desativado');
    } elseif ($acao === 'excluir') {
        deletaPlano($id);
        $msg_sucesso = traduz('admin_plano_excluido');
    }

    if ($msg_sucesso) {
        header('Location: planos.php?' . http_build_query(array_filter([
            'ciclo' => $_GET['ciclo'] ?? '',
            'sucesso' => $msg_sucesso,
        ])));
        exit;
    }
}

if (!empty($_GET['sucesso'])) {
    $msg_sucesso = $_GET['sucesso'];
}

$filtro = [];
if (!empty($_GET['ciclo'])) {
    $filtro['ciclo'] = $_GET['ciclo'];
}

$planos = listaPlanos($filtro);

$nomes_ciclo = [
    'mensal' => traduz('admin_mensal'),
    'trimestral' => traduz('admin_trimestral'),
    'anual' => traduz('admin_anual'),
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CalendarioIA — <?= traduz('admin_planos') ?></title>
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
        <h1><?= traduz('admin_planos') ?></h1>
        <div class="subtitulo"><?= count($planos) ?> <?= count($planos) !== 1 ? traduz('admin_planos_encontrados') : traduz('admin_plano_encontrado') ?></div>
      </div>
      <div class="espaco"></div>
      <button type="button" class="botao-cabecalho" onclick="document.getElementById('modal-criar').classList.add('aberto')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <?= traduz('admin_novo_plano') ?>
      </button>
    </header>
    <div class="admin-area">

      <?php if ($msg_sucesso): ?>
      <div class="sucesso-msg"><?= htmlspecialchars($msg_sucesso) ?></div>
      <?php endif; ?>
      <?php if ($msg_erro): ?>
      <div class="erro-msg" style="margin-bottom:14px;"><?= htmlspecialchars($msg_erro) ?></div>
      <?php endif; ?>

      <div class="painel">
        <div class="painel-cabecalho">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-4)"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          <h2 class="painel-titulo"><?= traduz('admin_lista_planos') ?></h2>
          <div class="espacador"></div>
          <form method="get" action="planos.php" style="display:flex;gap:8px;align-items:center;">
            <select name="ciclo" class="filtro-select" onchange="this.form.submit()">
              <option value=""><?= traduz('admin_todos_ciclos') ?></option>
              <option value="mensal" <?= ($_GET['ciclo'] ?? '') === 'mensal' ? 'selected' : '' ?>><?= traduz('admin_mensal') ?></option>
              <option value="trimestral" <?= ($_GET['ciclo'] ?? '') === 'trimestral' ? 'selected' : '' ?>><?= traduz('admin_trimestral') ?></option>
              <option value="anual" <?= ($_GET['ciclo'] ?? '') === 'anual' ? 'selected' : '' ?>><?= traduz('admin_anual') ?></option>
            </select>
          </form>
        </div>
        <?php if ($planos): ?>
        <table>
          <thead>
            <tr>
              <th><?= traduz('admin_nome') ?></th>
              <th><?= traduz('admin_descricao') ?></th>
              <th><?= traduz('admin_ciclo') ?></th>
              <th><?= traduz('admin_preco') ?></th>
              <th><?= traduz('admin_dias_teste') ?></th>
              <th><?= traduz('admin_status') ?></th>
              <th style="text-align:right;"><?= traduz('admin_acoes') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($planos as $p): ?>
            <tr>
              <td style="color:var(--ink);font-weight:600;"><?= htmlspecialchars($p['nome']) ?></td>
              <td><?= htmlspecialchars($p['descricao'] ?: '—') ?></td>
              <td>
                <?php
                  $cor_ciclo = match($p['ciclo']) {
                      'mensal' => 'azul',
                      'trimestral' => 'ambar',
                      'anual' => 'verde',
                      default => 'neutro',
                  };
                ?>
                <span class="selo <?= $cor_ciclo ?>"><?= htmlspecialchars($nomes_ciclo[$p['ciclo']] ?? $p['ciclo']) ?></span>
              </td>
              <td style="font-weight:600;">MX$<?= number_format((float)$p['preco'], 2, '.', ',') ?></td>
              <td>
                <?php if ((int)$p['dias_teste'] > 0): ?>
                  <span class="selo ambar"><?= $p['dias_teste'] ?> <?= traduz('admin_dias') ?></span>
                <?php else: ?>
                  <span class="selo neutro"><?= traduz('admin_sem_teste') ?></span>
                <?php endif; ?>
              </td>
              <td><span class="selo <?= $p['ativo'] ? 'verde' : 'vermelho' ?>"><span class="ponto"></span> <?= $p['ativo'] ? traduz('admin_ativo') : traduz('admin_inativo') ?></span></td>
              <td style="text-align:right;display:flex;gap:4px;justify-content:flex-end;">
                <button type="button" class="botao-acao" onclick="abrirEditar(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS | JSON_HEX_TAG)) ?>)"><?= traduz('admin_editar') ?></button>
                <form method="post" action="planos.php?<?= http_build_query(array_filter(['ciclo' => $_GET['ciclo'] ?? ''])) ?>" style="display:inline;">
                  <input type="hidden" name="id_plano" value="<?= $p['id_plano'] ?>" />
                  <?php if ($p['ativo']): ?>
                    <button type="submit" name="acao" value="desativar" class="botao-acao perigo"><?= traduz('admin_desativar') ?></button>
                  <?php else: ?>
                    <button type="submit" name="acao" value="ativar" class="botao-acao"><?= traduz('admin_ativar') ?></button>
                  <?php endif; ?>
                  <button type="submit" name="acao" value="excluir" class="botao-acao perigo" onclick="return confirm('<?= traduz('admin_excluir') ?>?')"><?= traduz('admin_excluir') ?></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="estado-vazio">
          <div class="icone-vazio">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          </div>
          <?= traduz('admin_nenhum_plano') ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal Criar Plano -->
<div id="modal-criar" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('aberto')">
  <div class="modal">
    <div class="modal-header">
      <h2><?= traduz('admin_novo_plano') ?></h2>
      <button type="button" class="modal-fechar" onclick="this.closest('.modal-overlay').classList.remove('aberto')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="post" action="planos.php?<?= http_build_query(array_filter(['ciclo' => $_GET['ciclo'] ?? ''])) ?>">
      <input type="hidden" name="acao" value="criar" />
      <div class="modal-body">
        <div class="campo">
          <label><?= traduz('admin_nome_plano') ?></label>
          <div class="campo-entrada">
            <input type="text" name="nome" placeholder="Ex: Pro, Básico, Premium" required />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_descricao') ?></label>
          <div class="campo-entrada">
            <input type="text" name="descricao" placeholder="<?= traduz('admin_descricao_placeholder') ?>" />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_ciclo') ?></label>
          <div class="campo-entrada">
            <select name="ciclo">
              <option value="mensal"><?= traduz('admin_mensal') ?></option>
              <option value="trimestral"><?= traduz('admin_trimestral') ?></option>
              <option value="anual"><?= traduz('admin_anual') ?></option>
            </select>
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_preco') ?> (MXN)</label>
          <div class="campo-entrada">
            <input type="number" name="preco" step="0.01" min="0.01" placeholder="64.00" required />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_dias_teste') ?></label>
          <div class="campo-entrada">
            <input type="number" name="dias_teste" min="0" value="0" placeholder="<?= traduz('admin_dias_teste_hint') ?>" />
          </div>
          <span style="font-size:11px;color:var(--ink-4);margin-top:2px;"><?= traduz('admin_dias_teste_hint') ?></span>
        </div>
        <div class="campo">
          <label><?= traduz('admin_etiqueta_texto') ?></label>
          <div class="campo-entrada">
            <input type="text" name="etiqueta_texto" placeholder="<?= traduz('admin_etiqueta_placeholder') ?>" />
          </div>
          <span style="font-size:11px;color:var(--ink-4);margin-top:2px;"><?= traduz('admin_etiqueta_hint') ?></span>
        </div>
        <div class="campo">
          <label><?= traduz('admin_etiqueta_cor') ?></label>
          <div class="campo-entrada">
            <select name="etiqueta_cor">
              <option value="amarelo"><?= traduz('admin_cor_amarelo') ?></option>
              <option value="azul"><?= traduz('admin_cor_azul') ?></option>
              <option value="verde"><?= traduz('admin_cor_verde') ?></option>
              <option value="vermelho"><?= traduz('admin_cor_vermelho') ?></option>
              <option value="roxo"><?= traduz('admin_cor_roxo') ?></option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="botao-pequeno botao-fantasma" onclick="this.closest('.modal-overlay').classList.remove('aberto')"><?= traduz('admin_cancelar') ?></button>
        <button type="submit" class="botao-pequeno botao-primario-pequeno"><?= traduz('admin_criar_plano') ?></button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Plano -->
<div id="modal-editar" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('aberto')">
  <div class="modal">
    <div class="modal-header">
      <h2><?= traduz('admin_editar_plano') ?></h2>
      <button type="button" class="modal-fechar" onclick="this.closest('.modal-overlay').classList.remove('aberto')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="post" action="planos.php?<?= http_build_query(array_filter(['ciclo' => $_GET['ciclo'] ?? ''])) ?>">
      <input type="hidden" name="acao" value="editar" />
      <input type="hidden" name="id_plano" id="editar-id" value="" />
      <div class="modal-body">
        <div class="campo">
          <label><?= traduz('admin_nome_plano') ?></label>
          <div class="campo-entrada">
            <input type="text" name="nome" id="editar-nome" required />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_descricao') ?></label>
          <div class="campo-entrada">
            <input type="text" name="descricao" id="editar-descricao" placeholder="<?= traduz('admin_descricao_placeholder') ?>" />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_ciclo') ?></label>
          <div class="campo-entrada">
            <select name="ciclo" id="editar-ciclo">
              <option value="mensal"><?= traduz('admin_mensal') ?></option>
              <option value="trimestral"><?= traduz('admin_trimestral') ?></option>
              <option value="anual"><?= traduz('admin_anual') ?></option>
            </select>
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_preco') ?> (MXN)</label>
          <div class="campo-entrada">
            <input type="number" name="preco" id="editar-preco" step="0.01" min="0.01" required />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_dias_teste') ?></label>
          <div class="campo-entrada">
            <input type="number" name="dias_teste" id="editar-dias-teste" min="0" />
          </div>
          <span style="font-size:11px;color:var(--ink-4);margin-top:2px;"><?= traduz('admin_dias_teste_hint') ?></span>
        </div>
        <div class="campo">
          <label><?= traduz('admin_etiqueta_texto') ?></label>
          <div class="campo-entrada">
            <input type="text" name="etiqueta_texto" id="editar-etiqueta-texto" placeholder="<?= traduz('admin_etiqueta_placeholder') ?>" />
          </div>
          <span style="font-size:11px;color:var(--ink-4);margin-top:2px;"><?= traduz('admin_etiqueta_hint') ?></span>
        </div>
        <div class="campo">
          <label><?= traduz('admin_etiqueta_cor') ?></label>
          <div class="campo-entrada">
            <select name="etiqueta_cor" id="editar-etiqueta-cor">
              <option value="amarelo"><?= traduz('admin_cor_amarelo') ?></option>
              <option value="azul"><?= traduz('admin_cor_azul') ?></option>
              <option value="verde"><?= traduz('admin_cor_verde') ?></option>
              <option value="vermelho"><?= traduz('admin_cor_vermelho') ?></option>
              <option value="roxo"><?= traduz('admin_cor_roxo') ?></option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="botao-pequeno botao-fantasma" onclick="this.closest('.modal-overlay').classList.remove('aberto')"><?= traduz('admin_cancelar') ?></button>
        <button type="submit" class="botao-pequeno botao-primario-pequeno"><?= traduz('admin_salvar') ?></button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirEditar(p) {
    document.getElementById('editar-id').value = p.id_plano;
    document.getElementById('editar-nome').value = p.nome;
    document.getElementById('editar-descricao').value = p.descricao || '';
    document.getElementById('editar-ciclo').value = p.ciclo;
    document.getElementById('editar-preco').value = parseFloat(p.preco).toFixed(2);
    document.getElementById('editar-dias-teste').value = p.dias_teste;
    document.getElementById('editar-etiqueta-texto').value = p.etiqueta_texto || '';
    document.getElementById('editar-etiqueta-cor').value = p.etiqueta_cor || 'amarelo';
    document.getElementById('modal-editar').classList.add('aberto');
}

<?php if ($msg_erro && isset($_POST['acao']) && $_POST['acao'] === 'criar'): ?>
document.getElementById('modal-criar').classList.add('aberto');
<?php elseif ($msg_erro && isset($_POST['acao']) && $_POST['acao'] === 'editar'): ?>
document.getElementById('modal-editar').classList.add('aberto');
<?php endif; ?>
</script>

</body>
</html>
