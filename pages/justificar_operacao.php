<?php
session_start();
include_once '../inc/config.php';
include_once '../inc/validacoes.php';
include_once '../repositories/OperacaoJsonRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'conferente') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repoOp = new OperacaoJsonRepository();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$operacao = $repoOp->buscarPorId($id);

if (!$operacao) {
    header('Location: dashboard_conferente_v2.php');
    exit;
}

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $justificativa = trim($_POST['justificativa'] ?? '');
    if (empty($justificativa) || strlen($justificativa) < 10) {
        $erro = 'Justificativa muito curta (mín. 10 caracteres).';
    } else {
        $repoOp->justificarOperacao($id, $usuario, $justificativa);
        header('Location: dashboard_conferente_v2.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Justificar Operação</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Justificar Operação</h2>
        <div>
            <span class="user-badge">👤 <?php echo htmlspecialchars($usuario['nome']); ?></span>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <div class="dashboard-content">

<a href="detalhes_operacao.php?id=<?php echo $id; ?>" class="btn-voltar" title="Voltar para a Operação (Alt + ←)">
    <span class="icone">←</span>
    <span class="texto">Voltar para a Operação</span>
</a>
            <div class="form-container">
                <h3>💬 Justificar que a Operação Está Correta</h3>
                <p style="color:#666; font-size:14px;">
                    O ADM reportou o seguinte erro no Booking <strong><?php echo htmlspecialchars($operacao['numero_booking']); ?></strong>:
                </p>
                <div class="alerta-notificacao" style="margin: 15px 0;">
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($operacao['tipo_erro']); ?></p>
                    <p><strong>Motivo:</strong> <?php echo htmlspecialchars($operacao['motivo_reporte']); ?></p>
                </div>
                <p style="color:#666; font-size:14px;">Se você acredita que a operação está correta, explique abaixo. O ADM verá a sua justificativa.</p>

                <?php if ($erro): ?>
                    <div class="erro"><?php echo $erro; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <label for="justificativa">Justificativa *</label>
                    <input type="text" id="justificativa" name="justificativa" placeholder="Ex: Peso confere sim, o container estava com sobra." maxlength="300" required minlength="10">
                    <button type="submit" style="background:#28a745;">💬 Enviar Justificativa</button>
                </form>
            </div>
        </div>
    </div>
<script src="../assets/js/validacao-tempo-real.js"></script>
<script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>
</body>
</html>