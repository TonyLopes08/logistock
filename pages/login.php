<?php
session_start();

include_once '../repositories/UsuarioRepository.php';

if (isset($_SESSION['usuario'])) {
    $perfil = $_SESSION['usuario']['perfil'];
    if ($perfil == 'adm') header('Location: dashboard_adm.php');
    elseif ($perfil == 'supervisor') header('Location: dashboard_supervisor.php');
    elseif ($perfil == 'conferente') header('Location: dashboard_conferente_v2.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cpf = $_POST['cpf'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $repo = new UsuarioRepository();
    $usuario = $repo->autenticar($cpf, $senha);

    if ($usuario) {
        unset($usuario['senha']);
        $_SESSION['usuario'] = $usuario;

        if ($usuario['perfil'] == 'adm') header('Location: dashboard_adm.php');
        elseif ($usuario['perfil'] == 'supervisor') header('Location: dashboard_supervisor.php');
        elseif ($usuario['perfil'] == 'conferente') header('Location: dashboard_conferente_v2.php');
        exit;
    } else {
        $erro = 'CPF ou senha inválidos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogiStock - Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #0b2b40; }
        .login-container { max-width: 420px; }
        .erro-login { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .creditos { margin-top: 20px; font-size: 12px; color: #999; text-align: center; line-height: 1.8; }
        .creditos strong { color: #0b2b40; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🚢 LogiStock</h1>
        <p class="subtitle">Sistema de Conferência Portuária</p>

        <?php if ($erro): ?>
            <div class="erro-login"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" maxlength="14" required autofocus>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>

            <button type="submit">Entrar no Sistema</button>
        </form>

        <div class="creditos">
            <strong>Teste com:</strong><br>
            ADM: 111.111.111-11<br>
            Supervisor: 222.222.222-22<br>
            Conferente: 333.333.333-33<br>
            Senha padrão: <strong>123</strong>
        </div>
    </div>

    <script>
        // Máscara de CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '').substring(0, 11);
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = v;
        });
    </script>

    <script src="../assets/js/ux.js"></script>
</body>
</html>