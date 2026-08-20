<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';
require_once __DIR__ . '/../funcoes/funcoesPlanos.php';
require_once __DIR__ . '/../funcoes/funcoesConfiguracao.php';
require_once __DIR__ . '/../funcoes/funcoesEventos.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'usuarios';
$msg_sucesso = '';
$msg_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $id = (int) ($_POST['id_usuario'] ?? 0);

    if ($acao === 'apagar') {
        deletaUsuario($id);
        $msg_sucesso = traduz('admin_usuario_apagado');
    } elseif ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $plano = $_POST['plano'] ?? 'trial';

        if (!$nome || !$email || !$senha) {
            $msg_erro = traduz('admin_erro_campos');
        } elseif (strlen($senha) < 6) {
            $msg_erro = traduz('admin_erro_senha_curta');
        } elseif (buscaUsuarioPorEmail($email)) {
            $msg_erro = traduz('admin_erro_email_existe');
        } elseif ($telefone !== '' && buscaUsuarioPorTelefone($telefone)) {
            $msg_erro = traduz('admin_erro_telefone_existe');
        } else {
            insereUsuarioAdmin([
                'nome' => $nome,
                'email' => $email,
                'senha' => $senha,
                'telefone' => $telefone,
                'plano' => $plano,
            ]);
            $msg_sucesso = traduz('admin_usuario_criado');
        }
    } elseif ($acao === 'editar') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $plano = $_POST['plano'] ?? 'trial';
        $plano_expira_em = $_POST['plano_expira_em'] ?? '';

        if (!$nome || !$email) {
            $msg_erro = traduz('admin_erro_nome_email');
        } else {
            $existente = buscaUsuarioPorEmail($email);
            if ($existente && (int)$existente['id_usuario'] !== $id) {
                $msg_erro = traduz('admin_erro_email_outro');
            } elseif ($telefone !== '') {
                $tel_existente = buscaUsuarioPorTelefone($telefone);
                if ($tel_existente && (int)$tel_existente['id_usuario'] !== $id) {
                    $msg_erro = traduz('admin_erro_telefone_outro');
                }
            }
            if (!$msg_erro) {
                atualizaUsuario($id, [
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone,
                    'plano' => $plano,
                    'plano_expira_em' => $plano_expira_em,
                ]);
                $msg_sucesso = traduz('admin_usuario_atualizado');
            }
        }
    }

    if ($msg_sucesso) {
        header('Location: usuarios?' . http_build_query(array_filter([
            'busca' => $_GET['busca'] ?? '',
            'plano' => $_GET['plano'] ?? '',
            'sucesso' => $msg_sucesso,
        ])));
        exit;
    }
}

if (!empty($_GET['sucesso'])) {
    $msg_sucesso = $_GET['sucesso'];
}

$filtro = [];
if (!empty($_GET['busca'])) {
    $filtro['busca'] = $_GET['busca'];
}
if (!empty($_GET['plano'])) {
    $filtro['plano'] = $_GET['plano'];
}

$usuarios = listaUsuarios($filtro);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg" />
<title><?= htmlspecialchars(nomeApp()) ?> — <?= traduz('admin_usuarios') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= versaoAsset('assets/css/admin.css') ?>" />
</head>
<body>
<div class="admin-estrutura">
  <?php require __DIR__ . '/_includes/sidebar.php'; ?>
  <div class="admin-conteudo">
    <header class="admin-barra">
      <div>
        <h1><?= traduz('admin_usuarios') ?></h1>
        <div class="subtitulo"><?= count($usuarios) ?> <?= count($usuarios) !== 1 ? traduz('admin_usuarios_encontrados') : traduz('admin_usuario_encontrado') ?></div>
      </div>
      <div class="espaco"></div>
      <button type="button" class="botao-cabecalho" onclick="document.getElementById('modal-criar').classList.add('aberto')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <?= traduz('admin_novo_usuario') ?>
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
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-4)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          <h2 class="painel-titulo"><?= traduz('admin_lista_usuarios') ?></h2>
          <div class="espacador"></div>
          <form method="get" action="usuarios" style="display:flex;gap:8px;align-items:center;">
            <div class="caixa-busca">
              <span class="icone"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
              <input type="text" name="busca" placeholder="<?= traduz('admin_buscar') ?>" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>" />
            </div>
            <select name="plano" class="filtro-select" onchange="this.form.submit()">
              <option value=""><?= traduz('admin_todos_planos') ?></option>
              <option value="trial" <?= ($_GET['plano'] ?? '') === 'trial' ? 'selected' : '' ?>>Trial</option>
              <option value="ativo" <?= ($_GET['plano'] ?? '') === 'ativo' ? 'selected' : '' ?>><?= traduz('admin_ativo') ?></option>
              <option value="cancelado" <?= ($_GET['plano'] ?? '') === 'cancelado' ? 'selected' : '' ?>><?= traduz('admin_cancelados') ?></option>
            </select>
          </form>
        </div>
        <?php if ($usuarios): ?>
        <table>
          <thead>
            <tr>
              <th><?= traduz('admin_nome') ?></th>
              <th><?= traduz('admin_email_col') ?></th>
              <th><?= traduz('admin_telefone') ?></th>
              <th><?= traduz('admin_plano') ?></th>
              <th><?= traduz('admin_status') ?></th>
              <th><?= traduz('admin_msgs_hoje') ?></th>
              <th><?= traduz('admin_cadastro') ?></th>
              <th style="text-align:right;"><?= traduz('admin_acoes') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
              <td style="color:var(--ink);font-weight:600;white-space:nowrap;"><?= htmlspecialchars($u['nome']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['telefone'] ?: '—') ?></td>
              <td>
                <?php
                  $cor_plano = match($u['plano']) {
                      'trial' => 'ambar',
                      'ativo' => 'verde',
                      'cancelado' => 'vermelho',
                      default => 'neutro',
                  };

                  if ($u['plano'] === 'trial' && $u['plano_expira_em']) {
                      $rotulo_data = traduz('admin_trial_termina');
                      $valor_data = date('d/m/Y H:i', strtotime($u['plano_expira_em']));
                  } elseif ($u['plano'] === 'ativo' && $u['plano_expira_em']) {
                      $rotulo_data = traduz('admin_proxima_cobranca');
                      $valor_data = date('d/m/Y H:i', strtotime($u['plano_expira_em']));
                  } elseif ($u['plano'] === 'cancelado' && !empty($u['cancelado_em'])) {
                      $rotulo_data = traduz('admin_cancelado_em');
                      $valor_data = date('d/m/Y H:i', strtotime($u['cancelado_em']));
                  } else {
                      $rotulo_data = traduz('admin_proxima_cobranca');
                      $valor_data = '—';
                  }

                  $pagamentos_usuario = array_map(function ($p) {
                      return [
                          'data' => date('d/m/Y', strtotime($p['criado_em'])),
                          'valor' => simboloMoeda() . number_format((float) $p['valor'], 2, ',', '.'),
                          'ciclo' => ucfirst($p['ciclo']),
                      ];
                  }, listaPagamentosUsuario($u['id_usuario']));

                  $mensagens_hoje_usuario = contaMensagensHojePorUsuario($u['id_usuario']);
                  $limite_mensagens_usuario = limiteDiarioMensagens();
                  $cor_msgs = $mensagens_hoje_usuario >= $limite_mensagens_usuario ? 'vermelho' : ($mensagens_hoje_usuario >= $limite_mensagens_usuario * 0.8 ? 'ambar' : 'neutro');

                  $u_visualizar = $u;
                  $u_visualizar['cor_plano'] = $cor_plano;
                  $u_visualizar['rotulo_data'] = $rotulo_data;
                  $u_visualizar['valor_data'] = $valor_data;
                  $u_visualizar['cadastro_fmt'] = date('d/m/Y', strtotime($u['criado_em']));
                  $u_visualizar['pagamentos'] = $pagamentos_usuario;
                  $u_visualizar['mensagens_hoje'] = $mensagens_hoje_usuario;
                  $u_visualizar['limite_mensagens'] = $limite_mensagens_usuario;
                ?>
                <div style="display:flex;align-items:center;gap:6px;">
                  <span style="font-weight:600;color:var(--ink);"><?= htmlspecialchars($u['nome_plano'] ?? '—') ?></span>
                  <span class="selo <?= $cor_plano ?>"><?= htmlspecialchars(ucfirst($u['plano'])) ?></span>
                </div>
              </td>
              <td><span class="selo <?= $u['ativo'] ? 'verde' : 'vermelho' ?>"><span class="ponto"></span> <?= $u['ativo'] ? traduz('admin_ativo') : traduz('admin_inativo') ?></span></td>
              <td><span class="selo <?= $cor_msgs ?>"><?= $mensagens_hoje_usuario ?>/<?= $limite_mensagens_usuario ?></span></td>
              <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
              <td style="text-align:right;display:flex;gap:2px;justify-content:flex-end;">
                <button type="button" class="botao-acao icone" title="<?= traduz('admin_visualizar') ?>" onclick="abrirVisualizar(<?= htmlspecialchars(json_encode($u_visualizar, JSON_HEX_APOS | JSON_HEX_TAG)) ?>)">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <button type="button" class="botao-acao icone" title="<?= traduz('admin_editar') ?>" onclick="abrirEditar(<?= htmlspecialchars(json_encode($u, JSON_HEX_APOS | JSON_HEX_TAG)) ?>)">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                </button>
                <form method="post" action="usuarios?<?= http_build_query(array_filter(['busca' => $_GET['busca'] ?? '', 'plano' => $_GET['plano'] ?? ''])) ?>" style="display:inline;" onsubmit="return confirm('<?= traduz('admin_confirmar_apagar') ?>')">
                  <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>" />
                  <button type="submit" name="acao" value="apagar" class="botao-acao icone perigo" title="<?= traduz('admin_apagar') ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="estado-vazio">
          <div class="icone-vazio">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <?= traduz('admin_nenhum_usuario') ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal Criar Usuário -->
<div id="modal-criar" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('aberto')">
  <div class="modal">
    <div class="modal-header">
      <h2><?= traduz('admin_novo_usuario') ?></h2>
      <button type="button" class="modal-fechar" onclick="this.closest('.modal-overlay').classList.remove('aberto')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="post" action="usuarios?<?= http_build_query(array_filter(['busca' => $_GET['busca'] ?? '', 'plano' => $_GET['plano'] ?? ''])) ?>">
      <input type="hidden" name="acao" value="criar" />
      <div class="modal-body">
        <div class="campo">
          <label><?= traduz('admin_nome') ?></label>
          <div class="campo-entrada">
            <input type="text" name="nome" placeholder="<?= traduz('admin_nome_completo') ?>" required />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_email_col') ?></label>
          <div class="campo-entrada">
            <input type="email" name="email" placeholder="email@exemplo.com" required />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_telefone') ?></label>
          <div class="campo-entrada">
            <input type="text" name="telefone" placeholder="+525512345678" />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_senha') ?></label>
          <div class="campo-entrada">
            <input type="password" name="senha" placeholder="<?= traduz('admin_min_senha') ?>" required minlength="6" />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_plano') ?></label>
          <div class="campo-entrada">
            <select name="plano">
              <option value="trial">Trial</option>
              <option value="ativo"><?= traduz('admin_ativo') ?></option>
              <option value="cancelado"><?= traduz('admin_cancelados') ?></option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="botao-pequeno botao-fantasma" onclick="this.closest('.modal-overlay').classList.remove('aberto')"><?= traduz('admin_cancelar') ?></button>
        <button type="submit" class="botao-pequeno botao-primario-pequeno"><?= traduz('admin_criar_usuario') ?></button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Usuário -->
<div id="modal-editar" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('aberto')">
  <div class="modal">
    <div class="modal-header">
      <h2><?= traduz('admin_editar_usuario') ?></h2>
      <button type="button" class="modal-fechar" onclick="this.closest('.modal-overlay').classList.remove('aberto')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="post" action="usuarios?<?= http_build_query(array_filter(['busca' => $_GET['busca'] ?? '', 'plano' => $_GET['plano'] ?? ''])) ?>">
      <input type="hidden" name="acao" value="editar" />
      <input type="hidden" name="id_usuario" id="editar-id" value="" />
      <div class="modal-body">
        <div class="campo">
          <label><?= traduz('admin_nome') ?></label>
          <div class="campo-entrada">
            <input type="text" name="nome" id="editar-nome" placeholder="<?= traduz('admin_nome_completo') ?>" required />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_email_col') ?></label>
          <div class="campo-entrada">
            <input type="email" name="email" id="editar-email" placeholder="email@exemplo.com" required />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_telefone') ?></label>
          <div class="campo-entrada">
            <input type="text" name="telefone" id="editar-telefone" placeholder="+525512345678" />
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_plano') ?></label>
          <div class="campo-entrada">
            <select name="plano" id="editar-plano">
              <option value="trial">Trial</option>
              <option value="ativo"><?= traduz('admin_ativo') ?></option>
              <option value="cancelado"><?= traduz('admin_cancelados') ?></option>
            </select>
          </div>
        </div>
        <div class="campo">
          <label><?= traduz('admin_expiracao_plano') ?></label>
          <div class="campo-entrada">
            <input type="datetime-local" name="plano_expira_em" id="editar-plano-expira" />
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

<!-- Modal Visualizar Usuário -->
<div id="modal-visualizar" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('aberto')">
  <div class="modal">
    <div class="modal-header">
      <h2><?= traduz('admin_detalhes_usuario') ?></h2>
      <button type="button" class="modal-fechar" onclick="this.closest('.modal-overlay').classList.remove('aberto')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="resumo-linha"><span><?= traduz('admin_nome') ?></span><b id="ver-nome"></b></div>
      <div class="resumo-linha"><span><?= traduz('admin_email_col') ?></span><b id="ver-email"></b></div>
      <div class="resumo-linha"><span><?= traduz('admin_telefone') ?></span><b id="ver-telefone"></b></div>
      <div class="resumo-linha"><span><?= traduz('admin_plano') ?></span><b id="ver-plano"></b></div>
      <div class="resumo-linha"><span id="ver-rotulo-data"></span><b id="ver-valor-data"></b></div>
      <div class="resumo-linha"><span><?= traduz('admin_msgs_hoje') ?></span><b id="ver-msgs-hoje"></b></div>
      <div class="resumo-linha"><span><?= traduz('admin_cadastro') ?></span><b id="ver-cadastro"></b></div>
      <div style="margin-top:16px;">
        <div class="secao-rotulo" style="margin-bottom:8px;"><?= traduz('admin_historico_pagamentos') ?></div>
        <table id="ver-tabela-pagamentos" style="display:none;">
          <thead>
            <tr>
              <th><?= traduz('admin_data') ?></th>
              <th><?= traduz('admin_valor') ?></th>
              <th><?= traduz('admin_ciclo') ?></th>
            </tr>
          </thead>
          <tbody id="ver-pagamentos-body"></tbody>
        </table>
        <div id="ver-sem-pagamentos" class="estado-vazio" style="display:none;">
          <?= traduz('admin_sem_pagamentos') ?>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="botao-pequeno botao-fantasma" onclick="this.closest('.modal-overlay').classList.remove('aberto')"><?= traduz('admin_fechar') ?></button>
    </div>
  </div>
</div>

<script>
function abrirVisualizar(u) {
    document.getElementById('ver-nome').textContent = u.nome;
    document.getElementById('ver-email').textContent = u.email;
    document.getElementById('ver-telefone').textContent = u.telefone || '—';
    document.getElementById('ver-plano').innerHTML = (u.nome_plano || '—') + ' &middot; <span class="selo ' + u.cor_plano + '">' + u.plano.charAt(0).toUpperCase() + u.plano.slice(1) + '</span>';
    document.getElementById('ver-rotulo-data').textContent = u.rotulo_data || '—';
    document.getElementById('ver-valor-data').textContent = u.valor_data || '—';
    document.getElementById('ver-msgs-hoje').textContent = (u.mensagens_hoje ?? '—') + '/' + (u.limite_mensagens ?? '—');
    document.getElementById('ver-cadastro').textContent = u.cadastro_fmt || '—';

    var tbody = document.getElementById('ver-pagamentos-body');
    tbody.innerHTML = '';
    if (u.pagamentos && u.pagamentos.length) {
        u.pagamentos.forEach(function (p) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + p.data + '</td><td>' + p.valor + '</td><td>' + p.ciclo + '</td>';
            tbody.appendChild(tr);
        });
        document.getElementById('ver-tabela-pagamentos').style.display = '';
        document.getElementById('ver-sem-pagamentos').style.display = 'none';
    } else {
        document.getElementById('ver-tabela-pagamentos').style.display = 'none';
        document.getElementById('ver-sem-pagamentos').style.display = '';
    }

    document.getElementById('modal-visualizar').classList.add('aberto');
}

function abrirEditar(u) {
    document.getElementById('editar-id').value = u.id_usuario;
    document.getElementById('editar-nome').value = u.nome;
    document.getElementById('editar-email').value = u.email;
    document.getElementById('editar-telefone').value = u.telefone || '';
    document.getElementById('editar-plano').value = u.plano;
    var expira = u.plano_expira_em || '';
    if (expira) {
        expira = expira.replace(' ', 'T').substring(0, 16);
    }
    document.getElementById('editar-plano-expira').value = expira;
    document.getElementById('modal-editar').classList.add('aberto');
}

<?php if ($msg_erro && isset($_POST['acao']) && $_POST['acao'] === 'criar'): ?>
document.getElementById('modal-criar').classList.add('aberto');
<?php elseif ($msg_erro && isset($_POST['acao']) && $_POST['acao'] === 'editar'): ?>
document.getElementById('modal-editar').classList.add('aberto');
<?php endif; ?>
</script>

<script src="../assets/js/mascote.js"></script>
</body>
</html>
