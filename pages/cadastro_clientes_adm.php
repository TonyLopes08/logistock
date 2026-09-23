<?php
session_start();
include_once '../inc/config.php';
include_once '../inc/validacoes.php';
include_once '../repositories/ClienteRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'adm') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repo = new ClienteRepository();
$mensagem = '';
$erro = '';
$modo_edicao = false;
$cliente_editar = null;

if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $cliente_editar = $repo->buscarPorId((int)$_GET['editar']);
    if ($cliente_editar) $modo_edicao = true;
}

if (isset($_GET['desativar']) && is_numeric($_GET['desativar'])) {
    $repo->desativar((int)$_GET['desativar']);
    header('Location: cadastro_clientes_adm.php');
    exit;
}
if (isset($_GET['reativar']) && is_numeric($_GET['reativar'])) {
    $repo->reativar((int)$_GET['reativar']);
    header('Location: cadastro_clientes_adm.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');

    $cnpj_limpo = preg_replace('/[^0-9]/', '', $cnpj);

    $erros = [];
    if (!validarNomeCliente($nome)) $erros[] = 'Nome inválido (3 a 100 caracteres, letras/números/espaços/hífen).';
    if (!validarCNPJ($cnpj_limpo)) $erros[] = 'CNPJ inválido (verifique os dígitos).';

    $cliente_existente = $repo->buscarPorCnpj($cnpj_limpo);
    if ($cliente_existente && $cliente_existente['id'] != $id) {
        $erros[] = 'Já existe um cliente com este CNPJ.';
    }

    if (count($erros) > 0) {
        $erro = implode('<br>', $erros);
    } else {
        if ($id > 0) {
            $repo->atualizar($id, ['nome' => $nome, 'cnpj' => $cnpj_limpo]);
            $mensagem = "✅ Cliente atualizado com sucesso!";
        } else {
            $repo->criar(['nome' => $nome, 'cnpj' => $cnpj_limpo]);
            $mensagem = "✅ Cliente cadastrado com sucesso!";
        }
        $modo_edicao = false;
        $cliente_editar = null;
    }
}

$clientes = $repo->listarTodos();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Cadastro de Clientes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Cadastro de Clientes</h2>
        <div>
            <span class="user-badge">👤 <?php echo htmlspecialchars($usuario['nome']); ?></span>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <div class="dashboard-content">

            <a href="dashboard_adm.php" class="btn-voltar" title="Voltar para o Painel Administrativo (Alt + ←)">
    <span class="icone">←</span>
    <span class="texto">Voltar ao Painel</span>
</a>
            
            <div class="form-container">
                <h3><?php echo $modo_edicao ? '✏️ Editar Cliente' : '➕ Cadastrar Novo Cliente'; ?></h3>
                <p style="color:#666; font-size:14px;">Cadastre os clientes das operações (exportadores, tradings, cooperativas).</p>

                <?php if ($mensagem): ?><div class="mensagem"><?php echo $mensagem; ?></div><?php endif; ?>
                <?php if ($erro): ?><div class="erro"><?php echo $erro; ?></div><?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo $modo_edicao ? $cliente_editar['id'] : ''; ?>">
                    <div class="form-row">
                        <div>
                            <label for="nome">Nome do Cliente *</label>
                            <input type="text" id="nome" name="nome" value="<?php echo $modo_edicao ? htmlspecialchars($cliente_editar['nome']) : ''; ?>" placeholder="Ex: Coperaguas" required maxlength="100">
                        </div>
                        <div>
                            <label for="cnpj">CNPJ *</label>
                            <input type="text" id="cnpj" name="cnpj" value="<?php echo $modo_edicao ? ClienteRepository::formatarCnpj($cliente_editar['cnpj']) : ''; ?>" placeholder="00.000.000/0000-00" maxlength="18" required>
                        </div>
                    </div>
                    <button type="submit"><?php echo $modo_edicao ? '💾 Salvar Alterações' : '📌 Cadastrar Cliente'; ?></button>
                    <?php if ($modo_edicao): ?>
                        <a href="cadastro_clientes_adm.php" style="display:inline-block; margin-top:10px; color:#666; text-decoration:none; font-size:14px;">Cancelar edição</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container">
                <h3 style="color: #0b2b40; margin-top: 0;">📋 Clientes Cadastrados</h3>
                <?php if (count($clientes) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td>#<?php echo $c['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td>
                            <td><?php echo ClienteRepository::formatarCnpj($c['cnpj']); ?></td>
                            <td><?php echo !empty($c['ativo']) ? '✅ Ativo' : '❌ Inativo'; ?></td>
                            <td>
                                <a href="?editar=<?php echo $c['id']; ?>" style="color:#0b2b40; text-decoration:none; font-weight:bold; font-size:13px;">✏️ Editar</a>
                                <?php if (!empty($c['ativo'])): ?>
                                    &nbsp;|&nbsp;<a href="?desativar=<?php echo $c['id']; ?>" style="color:#dc3545; text-decoration:none; font-size:13px;" onclick="return confirm('Desativar este cliente?')">🗑️ Desativar</a>
                                <?php else: ?>
                                    &nbsp;|&nbsp;<a href="?reativar=<?php echo $c['id']; ?>" style="color:#28a745; text-decoration:none; font-size:13px;">♻️ Reativar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="color:#999;">Nenhum cliente cadastrado ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Máscara de CNPJ
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '').substring(0, 14);
            v = v.replace(/^(\d{2})(\d)/, '$1.$2');
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
            v = v.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = v;
        });
    </script>

<script src="../assets/js/validacao-tempo-real.js"></script>
    <script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>
</body>
</html>