<?php
session_start();
include_once '../inc/config.php';
include_once '../inc/log_helper.php';
include_once '../repositories/OperacaoJsonRepository.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo '<p style="color:#dc3545;">Sessão expirada. Faça login novamente.</p>';
    exit;
}

$repoOp = new OperacaoJsonRepository();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$op = $repoOp->buscarPorId($id);

if (!$op) {
    echo '<p style="color:#dc3545;">Operação não encontrada.</p>';
    exit;
}

$tipos = [
    'armazem' => 'Armazém',
    'cross_docking' => 'Cross-Docking',
    'vagao_az_cntr' => 'Vagão-AZ-CNTR',
    'carregamento' => 'Carregamento',
    'descarregamento' => 'Descarregamento'
];
$tipo = $tipos[$op['tipo_operacao'] ?? 'armazem'] ?? '—';
$mao_de_obra = (($op['mao_de_obra'] ?? 'terminal') == 'sindicato') ? 'Sindicato' : 'Terminal (própria)';

$status_class = 'status-aberta';
if ($op['status'] == 'Em Andamento') $status_class = 'status-andamento';
if ($op['status'] == 'Finalizada') $status_class = 'status-finalizada';

$qtd_real = 0;
foreach (($op['itens'] ?? []) as $item) $qtd_real += (int)$item['quantidade'];
$peso_real = (float)($op['peso_bruto_total'] ?? 0);

$diff_qtd = $qtd_real - (int)($op['quantidade_estimada'] ?? 0);
$diff_peso = $peso_real - (float)($op['peso_estimado'] ?? 0);
?>

<div style="padding: 5px;">
    
    <!-- CABEÇALHO -->
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #0b2b40; padding-bottom:12px; margin-bottom:20px;">
        <div>
            <h3 style="margin:0; color:#0b2b40; font-size:20px;">📦 Booking <?php echo htmlspecialchars($op['numero_booking']); ?></h3>
            <small style="color:#666; font-size:12px;">ID #<?php echo $op['id']; ?> | Criado em <?php echo $op['data_criacao'] ?? '—'; ?></small>
        </div>
        <span class="<?php echo $status_class; ?>" style="font-size:13px;"><?php echo $op['status']; ?></span>
    </div>

    <!-- DADOS PRINCIPAIS -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:10px; margin-bottom:20px;">
        <div style="background:#f0f4f8; padding:10px; border-radius:6px; border-left:3px solid #0b2b40;">
            <small style="display:block; color:#666; font-size:11px; text-transform:uppercase;">Cliente</small>
            <strong style="color:#0b2b40; font-size:14px;"><?php echo htmlspecialchars($op['cliente_nome'] ?? '—'); ?></strong>
        </div>
        <div style="background:#f0f4f8; padding:10px; border-radius:6px; border-left:3px solid #0b2b40;">
            <small style="display:block; color:#666; font-size:11px; text-transform:uppercase;">Mercadoria</small>
            <strong style="color:#0b2b40; font-size:14px;"><?php echo htmlspecialchars($op['tipo_mercadoria'] ?? '—'); ?></strong>
        </div>
        <div style="background:#f0f4f8; padding:10px; border-radius:6px; border-left:3px solid #0b2b40;">
            <small style="display:block; color:#666; font-size:11px; text-transform:uppercase;">Container</small>
            <strong style="color:#0b2b40; font-size:14px;"><?php echo htmlspecialchars($op['container'] ?? '—'); ?></strong>
        </div>
        <div style="background:#f0f4f8; padding:10px; border-radius:6px; border-left:3px solid #0b2b40;">
            <small style="display:block; color:#666; font-size:11px; text-transform:uppercase;">Conferente</small>
            <strong style="color:#0b2b40; font-size:14px;"><?php echo htmlspecialchars($op['finalizada_por_nome'] ?? $op['conferente_nome'] ?? '—'); ?></strong>
        </div>
        <div style="background:#f0f4f8; padding:10px; border-radius:6px; border-left:3px solid #0b2b40;">
            <small style="display:block; color:#666; font-size:11px; text-transform:uppercase;">Tipo Operação</small>
            <strong style="color:#0b2b40; font-size:14px;"><?php echo htmlspecialchars($tipo); ?></strong>
        </div>
        <div style="background:#f0f4f8; padding:10px; border-radius:6px; border-left:3px solid #0b2b40;">
            <small style="display:block; color:#666; font-size:11px; text-transform:uppercase;">Mão de Obra</small>
            <strong style="color:#0b2b40; font-size:14px;"><?php echo htmlspecialchars($mao_de_obra); ?></strong>
        </div>
    </div>

    <!-- COMPARAÇÃO BOOKING vs REAL -->
    <div style="margin-bottom:20px;">
        <h4 style="margin:0 0 10px; color:#0b2b40; font-size:14px;">📊 Comparação Booking vs Real</h4>
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#0b2b40; color:white;">
                    <th style="padding:8px; text-align:left;">Item</th>
                    <th style="padding:8px; text-align:center;">Estimado</th>
                    <th style="padding:8px; text-align:center;">Real</th>
                    <th style="padding:8px; text-align:center;">Diferença</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:8px; border-bottom:1px solid #eee;"><strong>Quantidade</strong></td>
                    <td style="padding:8px; text-align:center; border-bottom:1px solid #eee;"><?php echo number_format($op['quantidade_estimada'] ?? 0, 0, ',', '.'); ?></td>
                    <td style="padding:8px; text-align:center; border-bottom:1px solid #eee;"><?php echo number_format($qtd_real, 0, ',', '.'); ?></td>
                    <td style="padding:8px; text-align:center; border-bottom:1px solid #eee; font-weight:bold; color: <?php echo ($diff_qtd == 0) ? '#28a745' : '#dc3545'; ?>;">
                        <?php echo ($diff_qtd == 0) ? '✔ Igual' : (($diff_qtd > 0 ? '+' : '') . $diff_qtd); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px;"><strong>Peso (kg)</strong></td>
                    <td style="padding:8px; text-align:center;"><?php echo number_format($op['peso_estimado'] ?? 0, 2, ',', '.'); ?></td>
                    <td style="padding:8px; text-align:center;"><?php echo number_format($peso_real, 2, ',', '.'); ?></td>
                    <td style="padding:8px; text-align:center; font-weight:bold; color: <?php echo (abs($diff_peso) < 0.01) ? '#28a745' : '#dc3545'; ?>;">
                        <?php echo (abs($diff_peso) < 0.01) ? '✔ Igual' : (($diff_peso > 0 ? '+' : '') . number_format($diff_peso, 2, ',', '.')); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ITENS DA CARGA -->
    <?php if (!empty($op['itens'])): ?>
    <div style="margin-bottom:20px;">
        <h4 style="margin:0 0 10px; color:#0b2b40; font-size:14px;">📋 Itens da Carga (<?php echo count($op['itens']); ?>)</h4>
        <div style="max-height:180px; overflow-y:auto; border:1px solid #e0e0e0; border-radius:6px;">
            <table style="width:100%; border-collapse:collapse; font-size:12px;">
                <thead>
                    <tr style="background:#f0f4f8;">
                        <th style="padding:8px; text-align:left; color:#555;">Descrição</th>
                        <th style="padding:8px; text-align:center; color:#555;">Qtd</th>
                        <th style="padding:8px; text-align:center; color:#555;">Unidade</th>
                        <th style="padding:8px; text-align:center; color:#555;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($op['itens'] as $item): 
                        $cor_status = '#ffc107';
                        if ($item['status_conferencia'] == 'Conferido') $cor_status = '#28a745';
                        if ($item['status_conferencia'] == 'Divergência') $cor_status = '#fd7e14';
                        if ($item['status_conferencia'] == 'Avariado') $cor_status = '#dc3545';
                    ?>
                    <tr>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;"><?php echo htmlspecialchars($item['descricao']); ?></td>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:center;"><?php echo $item['quantidade']; ?></td>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:center;"><?php echo htmlspecialchars($item['unidade'] ?? '—'); ?></td>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:center;">
                            <span style="background: <?php echo $cor_status; ?>; color:white; padding:2px 8px; border-radius:10px; font-size:11px;">
                                <?php echo $item['status_conferencia']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ALERTA DE REPORTE -->
    <?php if (!empty($op['reportado_pelo_adm'])): ?>
        <div style="background:#fff3cd; border-left:4px solid #ffc107; padding:12px; border-radius:6px; margin-bottom:15px; font-size:13px; color:#856404;">
            <strong>⚠️ Erro reportado:</strong> <?php echo htmlspecialchars($op['tipo_erro'] ?? ''); ?> — <?php echo htmlspecialchars($op['motivo_reporte'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <!-- BOTÃO VER COMPLETO -->
    <div style="text-align:center; padding-top:10px; border-top:1px solid #eee;">
        <a href="detalhes_operacao.php?id=<?php echo $op['id']; ?>" style="background:#0b2b40; color:white; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:14px; display:inline-block;">
            🔍 Abrir em Tela Cheia
        </a>
    </div>
</div>