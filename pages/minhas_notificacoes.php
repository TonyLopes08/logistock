<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/EmailRepository.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repo = new EmailRepository();
$emails = $repo->listarPorUsuario($usuario['email']);

// Marca todos como lidos ao abrir a tela
$repo->marcarTodosComoLidos($usuario['email']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Minhas Notificações</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Minhas Notificações</h2>
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
            <a href="<?php echo $voltar_url; ?>" class="btn-voltar" title="Voltar (Alt + ←)">
                <span class="icone">←</span>
                <span class="texto">Voltar ao Painel</span>
            </a>

            <h3 style="color: #0b2b40;">🔔 Minhas Notificações</h3>
            <p style="color:#555;">
                Aqui estão as notificações destinadas a você. Total: <strong><?php echo count($emails); ?></strong>.
            </p>

            <?php if (count($emails) > 0): ?>
                <?php foreach (array_reverse($emails) as $e): ?>
                    <div class="form-container" style="margin-bottom:15px; border-left: 5px solid <?php echo $e['tipo'] == 'reporte_erro' ? '#dc3545' : '#17a2b8'; ?>;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                            <div style="flex:1; min-width:200px;">
                                <strong style="color:#0b2b40; font-size:15px;">📩 <?php echo htmlspecialchars($e['assunto']); ?></strong>
                                <p style="margin:5px 0; color:#666; font-size:13px;">
                                    <strong>Tipo:</strong> 
                                    <span style="background: <?php echo $e['tipo'] == 'reporte_erro' ? '#dc3545' : '#17a2b8'; ?>; color:white; padding:2px 8px; border-radius:8px; font-size:11px;">
                                        <?php echo htmlspecialchars($e['tipo']); ?>
                                    </span>
                                    <?php if ($e['operacao_id']): ?>
                                        | <strong>Operação:</strong> #<?php echo $e['operacao_id']; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:11px; color:#999;">
                                    <?php echo $e['data_registro']; ?>
                                </div>
                            </div>
                        </div>
                        <details style="margin-top:10px;" open>
                            <summary style="cursor:pointer; color:#0b2b40; font-weight:bold; font-size:13px;">📄 Ver conteúdo</summary>
                            <pre style="background:#f8f9fa; padding:12px; border-radius:6px; margin-top:8px; white-space:pre-wrap; font-family:Arial, sans-serif; font-size:13px; color:#333; border:1px solid #e0e0e0;"><?php echo htmlspecialchars($e['corpo']); ?></pre>
                        </details>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:50px 20px; color:#999;">
                    <div style="font-size:60px; margin-bottom:15px;">📭</div>
                    <p>Você ainda não tem notificações.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/validacao-tempo-real.js"></script>
    <script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>
</body>
</html>