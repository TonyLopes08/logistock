<?php
session_start();
include_once '../inc/config.php';
include_once '../inc/validacoes.php';
include_once '../repositories/UsuarioRepository.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuario_sessao = $_SESSION['usuario'];
$repo = new UsuarioRepository();
$usuario = $repo->buscarPorId($usuario_sessao['id']);

if (!$usuario) {
    header('Location: logout.php');
    exit;
}

$mensagem = '';
$erro = '';

// --- TROCAR SENHA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'trocar_senha') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    $erros = [];
    if (empty($senha_atual)) $erros[] = 'Informe a senha atual.';
    if (empty($nova_senha) || strlen($nova_senha) < 3) $erros[] = 'Nova senha deve ter pelo menos 3 caracteres.';
    if ($nova_senha !== $confirmar) $erros[] = 'A confirmação de senha não confere.';
    if ($senha_atual === $nova_senha) $erros[] = 'A nova senha deve ser diferente da atual.';

    // Verifica senha atual
    if (empty($erros)) {
        $usuario_atual = $repo->autenticar($usuario['cpf'], $senha_atual);
        if (!$usuario_atual) {
            $erros[] = 'Senha atual incorreta.';
        }
    }

    if (count($erros) > 0) {
        $erro = implode('<br>', $erros);
    } else {
        $repo->atualizar($usuario['id'], ['senha' => $nova_senha]);
        $mensagem = "✅ Senha alterada com sucesso!";
    }
}

// --- EDITAR DADOS BÁSICOS (nome e email) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'editar_dados') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $erros = [];
    if (empty($nome) || strlen($nome) < 3 || strlen($nome) > 80) $erros[] = 'Nome inválido (3 a 80 caracteres).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';

    if (count($erros) > 0) {
        $erro = implode('<br>', $erros);
    } else {
        $repo->atualizar($usuario['id'], ['nome' => $nome, 'email' => $email]);
        // Atualiza a sessão
        $_SESSION['usuario']['nome'] = $nome;
        $_SESSION['usuario']['email'] = $email;
        $mensagem = "✅ Dados atualizados com sucesso!";
        $usuario = $repo->buscarPorId($usuario['id']);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Meu Perfil</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Meu Perfil</h2>
        <div>
            <span class="user-badge">👤 <?php echo htmlspecialchars($usuario['nome']); ?></span>
            <a href="logout.php" class="btn-sair" title="Sair do sistema">
                <span>🚪</span>
                <span>Sair</span>
            </a>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <div class="dashboard-content">

            <?php 
            $voltar_url = ($usuario['perfil'] == 'conferente') ? 'dashboard_conferente_v2.php' 
                        : (($usuario['perfil'] == 'supervisor') ? 'dashboard_supervisor.php' : 'dashboard_adm.php');
            ?>
            <a href="<?php echo $voltar_url; ?>" class="btn-voltar" title="Voltar para o Painel (Alt + ←)">
                <span class="icone">←</span>
                <span class="texto">Voltar ao Painel</span>
            </a>

            <h3 style="color: #0b2b40;">👤 Meu Perfil</h3>
            <p style="color: #555;">Aqui você pode ver e editar seus dados pessoais e trocar sua senha.</p>

            <?php if ($mensagem): ?><div class="mensagem"><?php echo $mensagem; ?></div><?php endif; ?>
            <?php if ($erro): ?><div class="erro"><?php echo $erro; ?></div><?php endif; ?>

            <!-- CARD: INFO PESSOAL -->
            <div class="form-container" style="margin-bottom: 20px;">
                <h4 style="margin-top:0; color:#0b2b40;">📋 Dados Pessoais</h4>

                <form method="POST" action="">
                    <input type="hidden" name="acao" value="editar_dados">

                    <div class="form-row">
                        <div>
                            <label>Nome Completo *</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required maxlength="80">
                        </div>
                        <div>
                            <label>E-mail *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required maxlength="100">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label>CPF (não editável)</label>
                            <input type="text" value="<?php echo UsuarioRepository::formatarCpf($usuario['cpf']); ?>" disabled style="background:#e9ecef; cursor:not-allowed;">
                        </div>
                        <div>
                            <label>Perfil (não editável)</label>
                            <input type="text" value="<?php echo strtoupper($usuario['perfil']); ?>" disabled style="background:#e9ecef; cursor:not-allowed;">
                        </div>
                    </div>

                    <button type="submit" style="background:#0b2b40;">💾 Salvar Dados</button>
                </form>
            </div>

            <!-- CARD: TROCAR SENHA -->
            <div class="form-container" style="border-left: 5px solid #dc3545;">
                <h4 style="margin-top:0; color:#dc3545;">🔒 Trocar Senha</h4>
                <p style="color:#666; font-size:14px; margin-top:0;">Use uma senha forte, com pelo menos 3 caracteres.</p>

                <form method="POST" action="">
                    <input type="hidden" name="acao" value="trocar_senha">

                    <div class="form-row">
                        <div>
                            <label>Senha Atual *</label>
                            <input type="password" name="senha_atual" placeholder="Digite sua senha atual" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label>Nova Senha *</label>
                            <input type="password" name="nova_senha" placeholder="Mín. 3 caracteres" required minlength="3">
                        </div>
                        <div>
                            <label>Confirmar Nova Senha *</label>
                            <input type="password" name="confirmar_senha" placeholder="Repita a nova senha" required minlength="3">
                        </div>
                    </div>

                    <button type="submit" style="background:#dc3545;">🔒 Alterar Senha</button>
                </form>
            </div>

        </div>
    </div>

    <script src="../assets/js/validacao-tempo-real.js"></script>
    <script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>

    <?php if ($mensagem): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            mostrarToast(<?php echo json_encode(strip_tags($mensagem)); ?>, 'sucesso');
        });
    </script>
    <?php endif; ?>

    <?php if ($erro): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            mostrarToast(<?php echo json_encode(strip_tags($erro)); ?>, 'erro', 5000);
        });
    </script>
    <?php endif; ?>
</body>
</html>