<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/UsuarioRepository.php';
include_once '../inc/validacoes.php';


if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'adm') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repo = new UsuarioRepository();
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? 'conferente';

    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);

    $erros = [];
    if (empty($nome) || strlen($nome) < 3 || strlen($nome) > 80) $erros[] = 'Nome inválido (3 a 80 caracteres).';
    if (!validarCPF($cpf_limpo)) $erros[] = 'CPF inválido (verifique os dígitos).';
    if ($repo->buscarPorCpf($cpf_limpo)) $erros[] = 'Já existe um usuário com este CPF.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (empty($senha) || strlen($senha) < 3) $erros[] = 'Senha deve ter pelo menos 3 caracteres.';
    if (!in_array($perfil, ['adm', 'supervisor', 'conferente'])) $erros[] = 'Perfil inválido.';

    if (count($erros) > 0) {
        $erro = implode('<br>', $erros);
    } else {
        $repo->criar([
            'nome' => $nome,
            'cpf' => $cpf_limpo,
            'email' => $email,
            'senha' => $senha,
            'perfil' => $perfil
        ]);
        $mensagem = "✅ Usuário <strong>" . htmlspecialchars($nome) . "</strong> cadastrado com sucesso!";
    }
}

$usuarios = $repo->listarTodos();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Cadastro de Usuários</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Cadastro de Usuários</h2>
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
            
            <!-- FORMULÁRIO DE CADASTRO -->
            <div class="form-container">
                <h3>➕ Cadastrar Novo Usuário</h3>
                <p style="color:#666; font-size:14px; margin-top:0;">Preencha os dados abaixo para adicionar um novo usuário ao sistema.</p>
                
                <?php if ($mensagem): ?>
                    <div class="mensagem"><?php echo $mensagem; ?></div>
                <?php endif; ?>
                <?php if ($erro): ?>
                    <div class="erro"><?php echo $erro; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-row">
                        <div>
                            <label for="nome">Nome Completo *</label>
                            <input type="text" id="nome" name="nome" placeholder="Ex: João da Silva" required maxlength="80">
                        </div>
                        <div>
                            <label for="cpf">CPF *</label>
                            <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" maxlength="14" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="email">E-mail *</label>
                            <input type="email" id="email" name="email" placeholder="usuario@logistock.com" required>
                        </div>
                        <div>
                            <label for="senha">Senha *</label>
                            <input type="text" id="senha" name="senha" placeholder="Mín. 3 caracteres" required minlength="3">
                        </div>
                        <div>
                            <label for="perfil">Perfil *</label>
                            <select id="perfil" name="perfil" required>
                                <option value="conferente">Conferente</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="adm">ADM</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit">📌 Cadastrar Usuário</button>
                </form>
            </div>

            <!-- LISTA DE USUÁRIOS -->
            <div class="table-container">
                <h3 style="color: #0b2b40; margin-top: 0;">📋 Usuários Cadastrados</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>E-mail</th>
                            <th>Perfil</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): 
                            $classe_perfil = 'perfil-' . $u['perfil'];
                        ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($u['nome']); ?></strong></td>
                            <td><?php echo UsuarioRepository::formatarCpf($u['cpf']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><span class="perfil-badge <?php echo $classe_perfil; ?>"><?php echo strtoupper($u['perfil']); ?></span></td>
                            <td><?php echo ($u['ativo'] ? '✅ Ativo' : '❌ Inativo'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <a href="dashboard_adm.php" class="voltar" style="display:inline-block; margin-top:20px; color:#0b2b40; text-decoration:none; font-weight:bold;">← Voltar ao Dashboard</a>
        </div>
    </div>

    <script>
        document.getElementById('cpf').addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '').substring(0, 11);
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = v;
        });
    </script>

<script src="../assets/js/validacao-tempo-real.js"></script>
    <script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>
</body>
</html>