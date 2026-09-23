<?php
session_start();
include_once '../inc/config.php';
include_once '../inc/validacoes.php';
include_once '../repositories/ProdutoRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'adm') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repo = new ProdutoRepository();
$mensagem = '';
$erro = '';
$modo_edicao = false;
$produto_editar = null;

// --- AÇÃO: EDITAR (carrega produto) ---
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $produto_editar = $repo->buscarPorId((int)$_GET['editar']);
    if ($produto_editar) $modo_edicao = true;
}

// --- AÇÃO: DESATIVAR / REATIVAR ---
if (isset($_GET['desativar']) && is_numeric($_GET['desativar'])) {
    $repo->desativar((int)$_GET['desativar']);
    header('Location: cadastro_produtos_adm.php');
    exit;
}
if (isset($_GET['reativar']) && is_numeric($_GET['reativar'])) {
    $repo->reativar((int)$_GET['reativar']);
    header('Location: cadastro_produtos_adm.php');
    exit;
}

// --- AÇÃO: SALVAR (CRIAR OU EDITAR) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $unidade = trim($_POST['unidade_padrao'] ?? '');
    $peso = str_replace(',', '.', $_POST['peso_unitario_padrao'] ?? 0);

    $erros = [];
    if (!validarTexto($nome, 100)) $erros[] = 'Nome inválido (3 a 100 caracteres).';
    if (empty($categoria) || !validarTexto($categoria, 50)) $erros[] = 'Categoria inválida.';
    if (empty($unidade) || strlen($unidade) > 30) $erros[] = 'Unidade inválida (máx. 30 caracteres).';
    if (!validarPeso($peso)) $erros[] = 'Peso unitário inválido (máx. 2 casas decimais).';

    if (count($erros) > 0) {
        $erro = implode('<br>', $erros);
    } else {
        if ($id > 0) {
            $repo->atualizar($id, [
                'nome' => $nome,
                'categoria' => $categoria,
                'unidade_padrao' => $unidade,
                'peso_unitario_padrao' => (float)$peso
            ]);
            $mensagem = "✅ Produto atualizado com sucesso!";
        } else {
            $repo->criar([
                'nome' => $nome,
                'categoria' => $categoria,
                'unidade_padrao' => $unidade,
                'peso_unitario_padrao' => (float)$peso
            ]);
            $mensagem = "✅ Produto cadastrado com sucesso!";
        }
        $modo_edicao = false;
        $produto_editar = null;
    }
}

$produtos = $repo->listarTodos();
$categorias = $repo->listarCategorias();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Cadastro de Mercadorias</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Cadastro de Mercadorias</h2>
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
                <h3><?php echo $modo_edicao ? '✏️ Editar Produto' : '➕ Cadastrar Novo Produto'; ?></h3>
                <p style="color:#666; font-size:14px;">Preencha os dados da mercadoria. Ela ficará disponível para seleção na hora de criar operações.</p>

                <?php if ($mensagem): ?><div class="mensagem"><?php echo $mensagem; ?></div><?php endif; ?>
                <?php if ($erro): ?><div class="erro"><?php echo $erro; ?></div><?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo $modo_edicao ? $produto_editar['id'] : ''; ?>">
                    
                    <div class="form-row">
                        <div>
                            <label for="nome">Nome do Produto *</label>
                            <input type="text" id="nome" name="nome" value="<?php echo $modo_edicao ? htmlspecialchars($produto_editar['nome']) : ''; ?>" placeholder="Ex: Soja em Grãos" required maxlength="100">
                        </div>
                        <div>
                            <label for="categoria">Categoria *</label>
                            <input type="text" id="categoria" name="categoria" list="lista-categorias" value="<?php echo $modo_edicao ? htmlspecialchars($produto_editar['categoria']) : ''; ?>" placeholder="Ex: Grãos, Madeira..." required maxlength="50">
                            <datalist id="lista-categorias">
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php endforeach; ?>
                                    <option value="Outros">
                                    </datalist>
                                </div>
                            </div>
                            <div class="form-row">
                                <div>
                                    <label for="unidade_padrao">Unidade Padrão *</label>
                                    <input type="text" id="unidade_padrao" name="unidade_padrao" value="<?php echo $modo_edicao ? htmlspecialchars($produto_editar['unidade_padrao']) : ''; ?>" placeholder="Ex: sacas 50kg, caixas" required maxlength="30">
                                </div>
                                <div>
                                    <label for="peso_unitario_padrao">Peso Unitário Padrão (kg) *</label>
                                    <input type="text" id="peso_unitario_padrao" name="peso_unitario_padrao" value="<?php echo $modo_edicao ? $produto_editar['peso_unitario_padrao'] : ''; ?>" placeholder="Ex: 50" pattern="[0-9]+([,.][0-9]+)?" required>
                                </div>
                            </div>
                            <button type="submit"><?php echo $modo_edicao ? '💾 Salvar Alterações' : '📌 Cadastrar Produto'; ?></button>
                            <?php if ($modo_edicao): ?>
                                <a href="cadastro_produtos_adm.php" style="display:inline-block; margin-top:10px; color:#666; text-decoration:none; font-size:14px;">Cancelar edição</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="table-container">
                        <h3 style="color: #0b2b40; margin-top: 0;">📋 Produtos Cadastrados</h3>
                        <?php if (count($produtos) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Categoria</th>
                                        <th>Unidade</th>
                                        <th>Peso Unit.</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($produtos as $p): ?>
                                        <tr>
                                            <td>#<?php echo $p['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($p['nome']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($p['categoria']); ?></td>
                                            <td><?php echo htmlspecialchars($p['unidade_padrao']); ?></td>
                                            <td><?php echo number_format($p['peso_unitario_padrao'], 2, ',', '.'); ?> kg</td>
                                            <td><?php echo !empty($p['ativo']) ? '✅ Ativo' : '❌ Inativo'; ?></td>
                                            <td>
                                                <a href="?editar=<?php echo $p['id']; ?>" style="color:#0b2b40; text-decoration:none; font-weight:bold; font-size:13px;">✏️ Editar</a>
                                                <?php if (!empty($p['ativo'])): ?>
                                                    &nbsp;|&nbsp;<a href="?desativar=<?php echo $p['id']; ?>" style="color:#dc3545; text-decoration:none; font-size:13px;" onclick="return confirm('Desativar este produto?')">🗑️ Desativar</a>
                                                <?php else: ?>
                                                    &nbsp;|&nbsp;<a href="?reativar=<?php echo $p['id']; ?>" style="color:#28a745; text-decoration:none; font-size:13px;">♻️ Reativar</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color:#999;">Nenhum produto cadastrado ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

<script src="../assets/js/validacao-tempo-real.js"></script>
            <script src="../assets/js/navegacao.js"></script>
            <script src="../assets/js/ux.js"></script>
        </body>
        </html>