<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/OperacaoJsonRepository.php';
include_once '../repositories/ClienteRepository.php';
include_once '../repositories/EmailRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'adm') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repoOp = new OperacaoJsonRepository();
$repoCliente = new ClienteRepository();
$clientes_lista = $repoCliente->listarAtivos();

$repoNotif = new EmailRepository();
$notifNaoLidas = $repoNotif->contarNaoLidosPorUsuario($usuario['email']);
$totalEmails = $repoNotif->contarPendentes();

$mostrar_arquivadas = isset($_GET['arquivadas']) && $_GET['arquivadas'] == '1';
$operacoes_base = $repoOp->listarParaAdm($mostrar_arquivadas ? 'apenas' : false);

$filtros = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'booking' => $_GET['booking'] ?? '',
    'cliente' => $_GET['cliente'] ?? '',
    'mostrar_tudo' => isset($_GET['mostrar_tudo']) && $_GET['mostrar_tudo'] == '1'
];

$operacoes = [];
foreach ($operacoes_base as $op) {
    if (!empty($filtros['data_inicio']) || !empty($filtros['data_fim'])) {
        $data_op = $op['data_termino'] ?? $op['data_inicio'] ?? null;
        if ($data_op) {
            $ts_op = strtotime(str_replace('/', '-', substr($data_op, 0, 10)));
            if (!empty($filtros['data_inicio'])) {
                $ts_ini = strtotime(str_replace('/', '-', $filtros['data_inicio']));
                if ($ts_op < $ts_ini) continue;
            }
            if (!empty($filtros['data_fim'])) {
                $ts_fim = strtotime(str_replace('/', '-', $filtros['data_fim']) . ' 23:59:59');
                if ($ts_op > $ts_fim) continue;
            }
        }
    }
    if (!empty($filtros['booking'])) {
        $busca = strtoupper(trim($filtros['booking']));
        if (strpos(strtoupper($op['numero_booking'] ?? ''), $busca) === false) continue;
    }
    if (!empty($filtros['cliente'])) {
        if (($op['cliente_nome'] ?? '') !== $filtros['cliente']) continue;
    }
    $operacoes[] = $op;
}

$total = count($operacoes);
$reportadas = 0;
$justificadas = 0;
$aguardando = 0;

foreach ($operacoes as $op) {
    if (!empty($op['reportado_pelo_adm'])) {
        if (!empty($op['justificado_pelo_conferente'])) {
            $justificadas++;
        } else {
            $reportadas++;
        }
    } else {
        $aguardando++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Administrativo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Administrativo</h2>
        <div>
            <?php if ($justificadas > 0): ?>
                <a href="dashboard_adm.php" class="notif-link" style="background:#17a2b8;">💬 <?php echo $justificadas; ?> justificativa(s)</a>
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
            <h3 style="color: #0b2b40;">📊 Painel Administrativo</h3>
            <p style="color:#555;">Analise as operações finalizadas pelos conferentes e reporte erros se necessário.</p>

            <div class="dashboard-cards">
                <div class="card-stat"><h3><?php echo $total; ?></h3><p>Total Finalizadas</p></div>
                <div class="card-stat" style="border-left-color:#dc3545;"><h3><?php echo $reportadas; ?></h3><p>Aguardando Resposta</p></div>
                <div class="card-stat" style="border-left-color:#17a2b8;"><h3><?php echo $justificadas; ?></h3><p>Justificadas</p></div>
                <div class="card-stat" style="border-left-color:#28a745;"><h3><?php echo $aguardando; ?></h3><p>Aguardando Análise</p></div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px;">
                <div class="card-stat" style="padding: 20px;">
                    <h4 style="margin-top:0; color:#0b2b40; font-size:14px;">📊 Operações por Status</h4>
                    <div class="grafico-container"><canvas id="grafico-status"></canvas></div>
                </div>
                <div class="card-stat" style="padding: 20px;">
                    <h4 style="margin-top:0; color:#0b2b40; font-size:14px;">📈 Finalizadas nos Últimos 7 Dias</h4>
                    <div class="grafico-container"><canvas id="grafico-dias"></canvas></div>
                </div>
                <div class="card-stat" style="padding: 20px;">
                    <h4 style="margin-top:0; color:#0b2b40; font-size:14px;">🏆 Top 5 Clientes</h4>
                    <div class="grafico-container"><canvas id="grafico-clientes"></canvas></div>
                </div>
            </div>

            <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="cadastro_usuarios.php" class="btn-secondary">👥 Gerenciar Usuários</a>
                <a href="cadastro_produtos_adm.php" class="btn-secondary" style="background:#17a2b8;">📦 Gerenciar Mercadorias</a>
                <a href="cadastro_clientes_adm.php" class="btn-secondary" style="background:#28a745;">🏢 Gerenciar Clientes</a>
                <a href="exportar_excel.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn-secondary" style="background:#20c997;">📈 Exportar Excel</a>
                <a href="auditoria_emails.php" class="btn-secondary" style="background:#6c757d;">
                    📧 Auditoria de E-mails <?php echo $totalEmails > 0 ? '(' . $totalEmails . ')' : ''; ?>
                </a>
                <?php if ($mostrar_arquivadas): ?>
                    <a href="dashboard_adm.php" class="btn-secondary" style="background:#0b2b40;">📋 Ver Operações Ativas</a>
                <?php else: ?>
                    <a href="?arquivadas=1" class="btn-secondary" style="background:#6c757d;">📦 Ver Arquivadas</a>
                <?php endif; ?>
            </div>

            <?php 
            $filtros_permitidos = ['data', 'booking', 'cliente'];
            $valores_atuais = $filtros;
            $url_base = $mostrar_arquivadas ? 'dashboard_adm.php?arquivadas=1' : 'dashboard_adm.php';
            include '../inc/filtros.php';
            ?>

            <div class="table-container">
                <h4 style="margin-top:0;">
                    <?php echo $mostrar_arquivadas ? '📦 Operações Arquivadas' : '📋 Operações Finalizadas'; ?>
                    (<?php echo $total; ?>)
                </h4>
                <?php if ($total > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Booking</th>
                                <th>Mercadoria</th>
                                <th>Conferente</th>
                                <th>Término</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($operacoes as $op): 
                                if (!empty($op['arquivada'])) {
                                    $status_class = 'status-finalizada';
                                    $status_label = '📦 Arquivada';
                                } elseif (!empty($op['justificado_pelo_conferente'])) {
                                    $status_class = 'status-andamento';
                                    $status_label = '💬 Justificado';
                                } elseif (!empty($op['reportado_pelo_adm'])) {
                                    $status_class = 'status-aguardando';
                                    $status_label = '⚠️ Reportado';
                                } else {
                                    $status_class = 'status-finalizada';
                                    $status_label = '✔ OK';
                                }
                                ?>
                                <tr>
                                    <td>#<?php echo $op['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($op['numero_booking']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($op['tipo_mercadoria']); ?></td>
                                    <td><?php echo htmlspecialchars($op['finalizada_por_nome'] ?? '—'); ?></td>
                                    <td><?php echo $op['data_termino'] ?? '—'; ?></td>
                                    <td><span class="<?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                    <td>
                                        <a href="detalhes_operacao.php?id=<?php echo $op['id']; ?>" 
                                           onclick="event.preventDefault(); abrirDetalhesModal(<?php echo $op['id']; ?>); return false;" 
                                           class="btn-acao ver">👁️ Ver</a>
                                        <?php if (!empty($op['arquivada'])): ?>
                                            <a href="arquivar_operacao.php?id=<?php echo $op['id']; ?>&acao=desarquivar" class="btn-acao desarquivar" onclick="return confirm('Desarquivar esta operação?')">📤 Desarquivar</a>
                                        <?php else: ?>
                                            <a href="analisar_operacao.php?id=<?php echo $op['id']; ?>" class="btn-acao analisar">🔍 Analisar</a>
                                            <a href="arquivar_operacao.php?id=<?php echo $op['id']; ?>" class="btn-acao arquivar" onclick="return confirm('Arquivar esta operação? Ela sairá da lista principal.')">📦 Arquivar</a>
                                        <?php endif; ?>
                                        <a href="gerar_packing_list.php?id=<?php echo $op['id']; ?>" class="btn-acao pdf">📄 PDF</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:#999;">Nenhuma operação <?php echo $mostrar_arquivadas ? 'arquivada' : 'finalizada'; ?> encontrada com os filtros aplicados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/chart.min.js"></script>
    <script>
        const dadosStatus = <?php 
            $todas = $repoOp->listarTodas();
            $status = ['Aberta' => 0, 'Em Andamento' => 0, 'Finalizada' => 0];
            foreach ($todas as $op) {
                if (isset($status[$op['status']])) $status[$op['status']]++;
            }
            echo json_encode($status); 
        ?>;

        new Chart(document.getElementById('grafico-status'), {
            type: 'doughnut',
            data: {
                labels: ['Aberta', 'Em Andamento', 'Finalizada'],
                datasets: [{
                    data: [dadosStatus.Aberta, dadosStatus['Em Andamento'], dadosStatus.Finalizada],
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('grafico-dias'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($repoOp->operacoesPorDia(7))); ?>,
                datasets: [{
                    label: 'Finalizadas',
                    data: <?php echo json_encode(array_values($repoOp->operacoesPorDia(7))); ?>,
                    borderColor: '#0b2b40',
                    backgroundColor: 'rgba(11,43,64,0.1)',
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

        new Chart(document.getElementById('grafico-clientes'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($repoOp->topClientes(5))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($repoOp->topClientes(5))); ?>,
                    backgroundColor: ['#0b2b40', '#1a4b66', '#17a2b8', '#28a745', '#ffc107'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { position: 'bottom' } } }
        });
    </script>
    <script src="../assets/js/validacao-tempo-real.js"></script>
    <script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>
</body>
</html>
