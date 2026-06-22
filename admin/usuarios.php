<?php
require_once __DIR__ . '/../funcoes/funcoesAuth.php';
require_once __DIR__ . '/../funcoes/funcoesUsuarios.php';
require_once __DIR__ . '/../funcoes/funcoesIdioma.php';

iniciaSessao();
exigeLoginAdmin();

$pagina_atual = 'usuarios';
$msg_sucesso = '';
$msg_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $id = (int) ($_POST['id_usuario'] ?? 0);

    if ($acao === 'ativar') {
        atualizaAtivoUsuario($id, true);
        $msg_sucesso = traduz('admin_usuario_ativado');
    } elseif ($acao === 'desativar') {
        atualizaAtivoUsuario($id, false);
        $msg_sucesso = traduz('admin_usuario_desativado');
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
            } else {
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
        header('Location: usuarios.php?' . http_build_query(array_filter([
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
<title>CalendarioIA — <?= traduz('admin_usuarios') ?></title>
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
          <form method="get" action="usuarios.php" style="display:flex;gap:8px;align-items:center;">
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
              <th><?= traduz('admin_cadastro') ?></th>
              <th style="text-align:right;"><?= traduz('admin_acoes') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
              <td style="color:var(--ink);font-weight:600;"><?= htmlspecialchars($u['nome']) ?></td>
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
                ?>
                <span class="selo <?= $cor_plano ?>"><?= htmlspecialchars(ucfirst($u['plano'])) ?></span>
              </td>
              <td><span class="selo <?= $u['ativo'] ? 'verde' : 'vermelho' ?>"><span class="ponto"></span> <?= $u['ativo'] ? traduz('admin_ativo') : traduz('admin_inativo') ?></span></td>
              <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
              <td style="text-align:right;display:flex;gap:4px;justify-content:flex-end;">
                <button type="button" class="botao-acao" onclick="abrirEditar(<?= htmlspecialchars(json_encode($u, JSON_HEX_APOS | JSON_HEX_TAG)) ?>)"><?= traduz('admin_editar') ?></button>
                <form method="post" action="usuarios.php?<?= http_build_query(array_filter(['busca' => $_GET['busca'] ?? '', 'plano' => $_GET['plano'] ?? ''])) ?>" style="display:inline;">
                  <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>" />
                  <?php if ($u['ativo']): ?>
                    <button type="submit" name="acao" value="desativar" class="botao-acao perigo"><?= traduz('admin_desativar') ?></button>
                  <?php else: ?>
                    <button type="submit" name="acao" value="ativar" class="botao-acao"><?= traduz('admin_ativar') ?></button>
                  <?php endif; ?>
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
    <form method="post" action="usuarios.php?<?= http_build_query(array_filter(['busca' => $_GET['busca'] ?? '', 'plano' => $_GET['plano'] ?? ''])) ?>">
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
    <form method="post" action="usuarios.php?<?= http_build_query(array_filter(['busca' => $_GET['busca'] ?? '', 'plano' => $_GET['plano'] ?? ''])) ?>">
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

<script>
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

</body>
</html>
