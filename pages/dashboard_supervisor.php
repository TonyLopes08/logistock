<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/OperacaoJsonRepository.php';
include_once '../repositories/ClienteRepository.php';
include_once '../repositories/EmailRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'supervisor') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repoOp = new OperacaoJsonRepository();
$repoCliente = new ClienteRepository();
$clientes_lista = $repoCliente->listarAtivos();

$repoNotif = new EmailRepository();
$notifNaoLidas = $repoNotif->contarNaoLidosPorUsuario($usuario['email']);

$filtros = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'booking' => $_GET['booking'] ?? '',
    'status' => $_GET['status'] ?? '',
    'cliente' => $_GET['cliente'] ?? '',
    'mostrar_tudo' => isset($_GET['mostrar_tudo']) && $_GET['mostrar_tudo'] == '1'
];

$operacoes = $repoOp->listarComFiltros($filtros);
$contagem = $repoOp->contarPorStatus();

$reportes_pendentes = 0;
foreach ($operacoes as $op) {
    if (!empty($op['reportado_pelo_adm']) && empty($op['justificado_pelo_conferente'])) $reportes_pendentes++;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Supervisor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Supervisor</h2>
        <div>
            <?php if ($reportes_pendentes > 0): ?>
                <a href="dashboard_supervisor.php" class="notif-link">⚠️ <?php echo $reportes_pendentes; ?> reporte(s)</a>
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
            <h3 style="color: #0b2b40;">📊 Painel do Supervisor</h3>
            <p style="color:#555;">Gerencie os Bookings e acompanhe todas as operações do terminal.</p>

            <div class="dashboard-cards">
                <div class="card-stat"><h3><?php echo count($operacoes); ?></h3><p>Total (filtradas)</p></div>
                <div class="card-stat" style="border-left-color:#ffc107;"><h3><?php echo $contagem['Aberta']; ?></h3><p>Abertas</p></div>
                <div class="card-stat" style="border-left-color:#17a2b8;"><h3><?php echo $contagem['Em Andamento']; ?></h3><p>Em Andamento</p></div>
                <div class="card-stat" style="border-left-color:#28a745;"><h3><?php echo $contagem['Finalizada']; ?></h3><p>Finalizadas</p></div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px;">
                <div class="card-stat" style="padding: 20px;">
                    <h4 style="margin-top:0; color:#0b2b40; font-size:14px;">👥 Operações por Conferente</h4>
                    <div class="grafico-container"><canvas id="grafico-conferentes"></canvas></div>
                </div>
                <div class="card-stat" style="padding: 20px;">
                    <h4 style="margin-top:0; color:#0b2b40; font-size:14px;">📈 Finalizadas nos Últimos 7 Dias</h4>
                    <div class="grafico-container"><canvas id="grafico-dias-sup"></canvas></div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <a href="criar_booking.php" class="btn-secondary">➕ Criar Novo Booking</a>
            </div>

            <?php 
            $filtros_permitidos = ['data', 'booking', 'status', 'cliente'];
            $valores_atuais = $filtros;
            $url_base = 'dashboard_supervisor.php';
            include '../inc/filtros.php';
            ?>

            <div class="table-container">
                <h4 style="margin-top:0;">📋 Operações (<?php echo count($operacoes); ?>)</h4>
                <?php if (count($operacoes) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Booking</th>
                            <th>Mercadoria</th>
                            <th>Conferente</th>
                            <th>Status</th>
                            <th>Início</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operacoes as $op): 
                            $status_class = 'status-aberta';
                            if ($op['status'] == 'Em Andamento') $status_class = 'status-andamento';
                            if ($op['status'] == 'Finalizada') $status_class = 'status-finalizada';
                            $tem_reporte = !empty($op['reportado_pelo_adm']) && empty($op['justificado_pelo_conferente']);
                        ?>
                        <tr>
                            <td>#<?php echo $op['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($op['numero_booking']); ?></strong>
                                <?php if ($tem_reporte): ?>
                                    <span style="background:#dc3545; color:white; padding:2px 6px; border-radius:8px; font-size:10px; margin-left:5px;">⚠️ REPORTE</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($op['tipo_mercadoria']); ?></td>
                            <td><?php echo htmlspecialchars($op['conferente_nome'] ?? '—'); ?></td>
                            <td><span class="<?php echo $status_class; ?>"><?php echo $op['status']; ?></span></td>
                            <td><?php echo $op['data_inicio'] ?? '—'; ?></td>
                            <td>
                                <a href="detalhes_operacao.php?id=<?php echo $op['id']; ?>" 
                                   onclick="event.preventDefault(); abrirDetalhesModal(<?php echo $op['id']; ?>); return false;" 
                                   class="btn-acao ver">👁️ Ver</a>
                                <?php if ($op['status'] == 'Aberta'): ?>
                                    <a href="excluir_operacao.php?id=<?php echo $op['id']; ?>" 
                                       class="btn-acao excluir"
                                       onclick="return confirm('Excluir esta operação? Isso é permanente.')">
                                       🗑️ Excluir
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="color:#999;">Nenhuma operação encontrada com os filtros aplicados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/chart.min.js"></script>
    <script>
        const coresBarras = ['#0b2b40', '#1a4b66', '#17a2b8', '#28a745', '#ffc107', '#fd7e14', '#dc3545', '#6f42c1', '#e83e8c', '#20c997'];
        const dadosConf = <?php echo json_encode(array_values($repoOp->operacoesPorConferente())); ?>;
        const coresPorConferente = dadosConf.map((_, i) => coresBarras[i % coresBarras.length]);

        new Chart(document.getElementById('grafico-conferentes'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($repoOp->operacoesPorConferente())); ?>,
                datasets: [{
                    label: 'Operações',
                    data: dadosConf,
                    backgroundColor: coresPorConferente,
                    barPercentage: 0.5,
                    categoryPercentage: 0.6,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }
            }
        });

        new Chart(document.getElementById('grafico-dias-sup'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($repoOp->operacoesPorDia(7))); ?>,
                datasets: [{
                    label: 'Finalizadas',
                    data: <?php echo json_encode(array_values($repoOp->operacoesPorDia(7))); ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40,167,69,0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }
            }
        });
    </script>
    <script src="../assets/js/validacao-tempo-real.js"></script>
    <script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>
</body>
</html>
