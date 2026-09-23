<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/OperacaoJsonRepository.php';
include_once '../repositories/EmailRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'conferente') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repoOp = new OperacaoJsonRepository();

$repoNotif = new EmailRepository();
$notifNaoLidas = $repoNotif->contarNaoLidosPorUsuario($usuario['email']);

$filtro_status = $_GET['status'] ?? '';
$operacoes_base = $repoOp->listarParaConferente($usuario['id']);

if (!empty($filtro_status)) {
    $operacoes = array_values(array_filter($operacoes_base, function($op) use ($filtro_status) {
        return $op['status'] === $filtro_status;
    }));
} else {
    $operacoes = $operacoes_base;
}

$notificacoes = $repoOp->listarNotificacoes($usuario['id']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Conferente</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Conferente</h2>
        <div>
            <?php if (count($notificacoes) > 0): ?>
                <a href="#notificacoes" class="notif-link" onclick="document.getElementById('notificacoes').scrollIntoView({behavior:'smooth'}); return false;">
                    🔔 <?php echo count($notificacoes); ?> notificação(ões)
                </a>
            <?php endif; ?>
            <span class="user-badge">👤 <?php echo htmlspecialchars($usuario['nome']); ?></span>
            <a href="minhas_notificacoes.php" class="btn-header notif" title="Minhas Notificações">
                <span>🔔</span>
                <span>Notificações <?php echo $notifNaoLidas > 0 ? '(' . $notifNaoLidas . ')' : ''; ?></span>
            </a>
            <a href="meu_perfil.php" class="btn-header perfil" title="Meu Perfil">
                <span>👤</span>
                <span>Perfil</span>
            </a>
            <a href="logout.php" class="btn-header sair" title="Sair do sistema">
                <span>🚪</span>
                <span>Sair</span>
            </a>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <div class="dashboard-content">
            <h3 style="color: #0b2b40;">📋 Minhas Operações</h3>
            <p style="color:#555;">Pegue Bookings abertos, continue operações em andamento ou revise notificações do ADM.</p>

            <?php if (count($notificacoes) > 0): ?>
                <div id="notificacoes" class="alerta-notificacao">
                    <strong>⚠️ Atenção! O ADM reportou erro(s) em operação(ões) que você finalizou.</strong>
                    <p>Revise abaixo e reabra se necessário:</p>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($notificacoes as $n): ?>
                            <li>
                                <a href="detalhes_operacao.php?id=<?php echo $n['id']; ?>">
                                    Booking <?php echo htmlspecialchars($n['numero_booking']); ?> 
                                    (motivo: <?php echo htmlspecialchars($n['motivo_reporte'] ?? 'não informado'); ?>)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="barra-filtros" style="border-left-color: #28a745;">
                <form method="GET" action="dashboard_conferente_v2.php" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <label style="font-weight:600; color:#555;">Filtrar por status:</label>
                    <select name="status" style="padding:8px 10px; border:1px solid #ccc; border-radius:6px;">
                        <option value="">Todos</option>
                        <option value="Aberta" <?php echo $filtro_status == 'Aberta' ? 'selected' : ''; ?>>Aberta</option>
                        <option value="Em Andamento" <?php echo $filtro_status == 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="Finalizada" <?php echo $filtro_status == 'Finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                    </select>
                    <button type="submit" class="btn-filtrar" style="background:#28a745;">🔍 Filtrar</button>
                    <?php if ($filtro_status): ?>
                        <a href="dashboard_conferente_v2.php" class="btn-limpar">✖ Limpar</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (count($operacoes) > 0): ?>
                <?php foreach ($operacoes as $op): 
                    $status_class = 'status-aberta';
                    if ($op['status'] == 'Em Andamento') $status_class = 'status-andamento';
                    if ($op['status'] == 'Finalizada') $status_class = 'status-finalizada';
                    
                    $total_itens = count($op['itens']);
                    $conferidos = 0;
                    foreach ($op['itens'] as $item) {
                        if ($item['status_conferencia'] != 'Pendente') $conferidos++;
                    }
                ?>
                <div class="card-operacao">
                    <div>
                        <strong>📦 Booking: <?php echo htmlspecialchars($op['numero_booking']); ?></strong>
                        <span style="display:block; color:#555;">
                            <span class="<?php echo $status_class; ?>"><?php echo $op['status']; ?></span>
                            &nbsp;| Mercadoria: <?php echo htmlspecialchars($op['tipo_mercadoria']); ?>
                            &nbsp;| Estimado: <?php echo $op['quantidade_estimada']; ?> un.
                            <?php if ($total_itens > 0): ?>
                                &nbsp;| Itens conferidos: <?php echo $conferidos; ?>/<?php echo $total_itens; ?>
                            <?php endif; ?>
                        </span>
                        <span style="font-size:13px; color:#888;">
                            <?php if ($op['conferente_nome']): ?>Conferente: <?php echo htmlspecialchars($op['conferente_nome']); ?> | <?php endif; ?>
                            <?php if ($op['container']): ?>Container: <?php echo htmlspecialchars($op['container']); ?><?php endif; ?>
                        </span>
                    </div>
                    <div>
                        <?php if ($op['status'] == 'Aberta'): ?>
                            <a href="pegar_operacao.php?id=<?php echo $op['id']; ?>" class="btn-packing" onclick="return confirm('Pegar este Booking?')">✋ Pegar Booking</a>
                        <?php elseif ($op['status'] == 'Em Andamento'): ?>
                            <a href="detalhes_operacao.php?id=<?php echo $op['id']; ?>" class="btn-conferir">🔍 Continuar</a>
                        <?php elseif ($op['status'] == 'Finalizada'): ?>
                            <a href="detalhes_operacao.php?id=<?php echo $op['id']; ?>" class="btn-conferir">👁️ Ver / Revisar</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="sem-operacoes">
                    🚫 Nenhuma operação disponível no momento.<br>
                    <span style="font-size:14px;">Aguarde o Supervisor criar um novo Booking.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/validacao-tempo-real.js"></script>
    <script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>
</body>
</html>