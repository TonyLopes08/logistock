<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/OperacaoJsonRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'adm') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repoOp = new OperacaoJsonRepository();

$filtros = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'booking' => $_GET['booking'] ?? '',
    'cliente' => $_GET['cliente'] ?? '',
    'mostrar_tudo' => isset($_GET['mostrar_tudo']) && $_GET['mostrar_tudo'] == '1'
];

$todas = $repoOp->listarComFiltros($filtros);
$operacoes = array_values(array_filter($todas, function($op) {
    return $op['status'] == 'Finalizada';
}));

$nome_arquivo = 'logistock_relatorio_' . date('Y-m-d_H-i') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo chr(0xEF).chr(0xBB).chr(0xBF);

$tipos = [
    'armazem' => 'Armazém',
    'cross_docking' => 'Cross-Docking',
    'vagao_az_cntr' => 'Vagão-AZ-CNTR',
    'carregamento' => 'Carregamento',
    'descarregamento' => 'Descarregamento',
];
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; }
        .titulo { background: #0b2b40; color: white; font-size: 18px; font-weight: bold; padding: 15px; text-align: center; }
        .subtitulo { background: #1a4b66; color: white; font-size: 12px; padding: 8px; text-align: center; }
        th { background: #1a4b66; color: white; padding: 8px; border: 1px solid #999; font-weight: bold; text-align: center; }
        td { padding: 6px 8px; border: 1px solid #ccc; }
        .linha-par { background: #f0f4f8; }
        .linha-impar { background: #ffffff; }
        .centro { text-align: center; }
        .direita { text-align: right; }
        .negrito { font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="18" class="titulo">LOGISTOCK - RELATÓRIO DE OPERAÇÕES FINALIZADAS</td>
        </tr>
        <tr>
            <td colspan="18" class="subtitulo">
                Gerado em <?php echo date('d/m/Y \à\s H:i:s'); ?> | Total: <?php echo count($operacoes); ?> operação(ões)
            </td>
        </tr>
        <tr><td colspan="18" style="height:10px; border:none;"></td></tr>
        <tr>
            <th style="width: 50px;">ID</th>
            <th style="width: 120px;">Booking</th>
            <th style="width: 140px;">Cliente</th>
            <th style="width: 150px;">Mercadoria</th>
            <th style="width: 120px;">Container</th>
            <th style="width: 120px;">Lacre</th>
            <th style="width: 140px;">Navio (M/V)</th>
            <th style="width: 120px;">Armador</th>
            <th style="width: 120px;">Tipo Operação</th>
            <th style="width: 110px;">Mão de Obra</th>
            <th style="width: 100px;">Qtd. Estimada</th>
            <th style="width: 110px;">Peso Est. (kg)</th>
            <th style="width: 90px;">Qtd. Real</th>
            <th style="width: 110px;">Peso Real (kg)</th>
            <th style="width: 140px;">Conferente (Pegou)</th>
            <th style="width: 140px;">Conferente (Finalizou)</th>
            <th style="width: 120px;">Início</th>
            <th style="width: 120px;">Término</th>
        </tr>
        <?php foreach ($operacoes as $i => $op): 
            $qtd_real = 0;
            foreach (($op['itens'] ?? []) as $item) $qtd_real += (int)$item['quantidade'];
            $tipo = $tipos[$op['tipo_operacao'] ?? 'armazem'] ?? ($op['tipo_operacao'] ?? '—');
            $mao_de_obra = (($op['mao_de_obra'] ?? 'terminal') == 'sindicato') ? 'Sindicato' : 'Terminal';
            $classe = ($i % 2 == 0) ? 'linha-par' : 'linha-impar';
        ?>
        <tr class="<?php echo $classe; ?>">
            <td class="centro negrito"><?php echo $op['id'] ?? ''; ?></td>
            <td class="centro"><?php echo htmlspecialchars($op['numero_booking'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($op['cliente_nome'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($op['tipo_mercadoria'] ?? ''); ?></td>
            <td class="centro"><?php echo htmlspecialchars($op['container'] ?? ''); ?></td>
            <td class="centro"><?php echo htmlspecialchars($op['lacre'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($op['navio'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($op['armador'] ?? ''); ?></td>
            <td class="centro"><?php echo htmlspecialchars($tipo); ?></td>
            <td class="centro"><?php echo htmlspecialchars($mao_de_obra); ?></td>
            <td class="centro"><?php echo number_format($op['quantidade_estimada'] ?? 0, 0, ',', '.'); ?></td>
            <td class="direita"><?php echo number_format((float)($op['peso_estimado'] ?? 0), 2, ',', '.'); ?></td>
            <td class="centro"><?php echo number_format($qtd_real, 0, ',', '.'); ?></td>
            <td class="direita"><?php echo number_format((float)($op['peso_bruto_total'] ?? 0), 2, ',', '.'); ?></td>
            <td><?php echo htmlspecialchars($op['conferente_nome'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($op['finalizada_por_nome'] ?? ''); ?></td>
            <td class="centro"><?php echo $op['data_inicio'] ?? ''; ?></td>
            <td class="centro"><?php echo $op['data_termino'] ?? ''; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>