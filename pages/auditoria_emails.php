<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/EmailRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'adm') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repo = new EmailRepository();
$emails = $repo->listarTodos();
$pendentes = $repo->contarPendentes();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Auditoria de E-mails</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Auditoria de E-mails</h2>
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
            <a href="dashboard_adm.php" class="btn-voltar" title="Voltar para o Painel (Alt + ←)">
                <span class="icone">←</span>
                <span class="texto">Voltar ao Painel</span>
            </a>

            <h3 style="color: #0b2b40;">📧 Auditoria de E-mails Simulados</h3>
            <div style="background:#fff3cd; border-left:4px solid #ffc107; padding:12px 15px; border-radius:6px; margin:15px 0; font-size:13px; color:#856404;">
                <strong>ℹ️ Nota:</strong> esta tela mostra <strong>TODOS</strong> os e-mails que o sistema 
                <strong>enviaria</strong> se o SMTP estivesse configurado. É uma tela técnica de auditoria 
                para o administrador. Os usuários veem apenas suas próprias notificações em "Minhas Notificações".
            </div>
            <p style="color:#555;">
                Total: <strong><?php echo count($emails); ?></strong> 
                (Pendentes de envio: <strong style="color:#fd7e14;"><?php echo $pendentes; ?></strong>)
            </p>

            <?php if (count($emails) > 0): ?>
                <?php foreach (array_reverse($emails) as $e): ?>
                    <div class="form-container" style="margin-bottom:15px; border-left: 5px solid <?php echo $e['enviado'] ? '#28a745' : '#fd7e14'; ?>;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                            <div style="flex:1; min-width:200px;">
                                <strong style="color:#0b2b40; font-size:14px;">📩 <?php echo htmlspecialchars($e['assunto']); ?></strong>
                                <p style="margin:5px 0; color:#666; font-size:13px;">
                                    <strong>Para:</strong> <?php echo htmlspecialchars($e['para_nome']); ?> 
                                    (<?php echo htmlspecialchars($e['para_email']); ?>)
                                </p>
                                <p style="margin:5px 0; color:#666; font-size:13px;">
                                    <strong>Tipo:</strong> <?php echo htmlspecialchars($e['tipo']); ?>
                                    <?php if ($e['operacao_id']): ?>
                                        | <strong>Operação:</strong> #<?php echo $e['operacao_id']; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div style="text-align:right;">
                                <span style="background:<?php echo $e['enviado'] ? '#28a745' : '#fd7e14'; ?>; color:white; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:bold;">
                                    <?php echo $e['enviado'] ? '✅ Enviado' : '⏳ Pendente'; ?>
                                </span>
                                <?php if (!empty($e['lido'])): ?>
                                    <div style="background:#17a2b8; color:white; padding:2px 8px; border-radius:8px; font-size:11px; margin-top:5px;">👁️ Lido</div>
                                <?php endif; ?>
                                <div style="font-size:11px; color:#999; margin-top:5px;">
                                    <?php echo $e['data_registro']; ?>
                                </div>
                            </div>
                        </div>
                        <details style="margin-top:10px;">
                            <summary style="cursor:pointer; color:#0b2b40; font-weight:bold; font-size:13px;">📄 Ver conteúdo do e-mail</summary>
                            <pre style="background:#f8f9fa; padding:12px; border-radius:6px; margin-top:8px; white-space:pre-wrap; font-family:Arial, sans-serif; font-size:13px; color:#333; border:1px solid #e0e0e0;"><?php echo htmlspecialchars($e['corpo']); ?></pre>
                        </details>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#999;">Nenhum e-mail simulado foi registrado ainda.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/validacao-tempo-real.js"></script>
    <script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>
</body>
</html>