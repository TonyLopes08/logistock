<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Erro interno</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #0b2b40;">
    <div style="background: white; padding: 50px 40px; border-radius: 16px; text-align: center; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.4);">
        <div style="font-size: 80px; margin-bottom: 10px;">💥</div>
        <h1 style="color: #dc3545; font-size: 60px; margin: 0;">500</h1>
        <h2 style="color: #0b2b40; font-size: 22px; margin: 10px 0 20px;">Erro interno no servidor</h2>
        <p style="color: #666; font-size: 15px; line-height: 1.6; margin-bottom: 30px;">
            Algo deu errado ao processar sua requisição. Nossa equipe já foi notificada.<br>
            Tente novamente em alguns instantes.
        </p>
        <a href="<?php echo isset($_SESSION['usuario']) ? (
            $_SESSION['usuario']['perfil'] == 'adm' ? 'dashboard_adm.php' :
            ($_SESSION['usuario']['perfil'] == 'supervisor' ? 'dashboard_supervisor.php' : 'dashboard_conferente_v2.php')
        ) : 'login.php'; ?>" 
        style="background: #0b2b40; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block;">
            ← Voltar ao Painel
        </a>
    </div>
</body>
</html>