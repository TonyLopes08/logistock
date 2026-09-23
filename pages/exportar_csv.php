<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/OperacaoJsonRepository.php';
include_once '../repositories/ClienteRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'adm') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repoOp = new OperacaoJsonRepository();
$repoCliente = new ClienteRepository();

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

$nome_arquivo = 'logistock_operacoes_' . date('Y-m-d_H-i') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeçalho
fputcsv($output, [
    'ID',
    'Booking',
    'Cliente',
    'Mercadoria',
    'Container',
    'Lacre',
    'Navio (M/V)',
    'Armador',
    'Tipo de Operação',
    'Mão de Obra',
    'Tara (kg)',
    'VGM (kg)',
    'Qtd. Estimada',
    'Peso Estimado (kg)',
    'Qtd. Real',
    'Peso Real (kg)',
    'Conferente (Pegou)',
    'Conferente (Finalizou)',
    'Data Início',
    'Data Término',
    'Status',
    'Reportado pelo ADM?',
    'Tipo de Erro',
    'Motivo do Reporte',
    'Justificado pelo Conferente?',
    'Justificativa'
], ';', '"', '\\');

// Linhas
foreach ($operacoes as $op) {
    $qtd_real = 0;
    foreach (($op['itens'] ?? []) as $item) {
        $qtd_real += (int)$item['quantidade'];
    }

    $tipos = [
        'armazem' => 'Armazém',
        'cross_docking' => 'Cross-Docking',
        'vagao_az_cntr' => 'Vagão-AZ-CNTR',
        'carregamento' => 'Carregamento',
        'descarregamento' => 'Descarregamento'
    ];
    $tipo = $tipos[$op['tipo_operacao'] ?? 'armazem'] ?? ($op['tipo_operacao'] ?? '—');

    $mao_de_obra = (($op['mao_de_obra'] ?? 'terminal') == 'sindicato') ? 'Sindicato' : 'Terminal (própria)';

    fputcsv($output, [
        $op['id'] ?? '',
        $op['numero_booking'] ?? '',
        $op['cliente_nome'] ?? '',
        $op['tipo_mercadoria'] ?? '',
        $op['container'] ?? '',
        $op['lacre'] ?? '',
        $op['navio'] ?? '',
        $op['armador'] ?? '',
        $tipo,
        $mao_de_obra,
        number_format((float)($op['tara'] ?? 0), 2, ',', '.'),
        number_format((float)($op['vgm'] ?? 0), 2, ',', '.'),
        $op['quantidade_estimada'] ?? 0,
        number_format((float)($op['peso_estimado'] ?? 0), 2, ',', '.'),
        $qtd_real,
        number_format((float)($op['peso_bruto_total'] ?? 0), 2, ',', '.'),
        $op['conferente_nome'] ?? '',
        $op['finalizada_por_nome'] ?? '',
        $op['data_inicio'] ?? '',
        $op['data_termino'] ?? '',
        $op['status'] ?? '',
        !empty($op['reportado_pelo_adm']) ? 'Sim' : 'Não',
        $op['tipo_erro'] ?? '',
        $op['motivo_reporte'] ?? '',
        !empty($op['justificado_pelo_conferente']) ? 'Sim' : 'Não',
        $op['justificativa_conferente'] ?? ''
    ], ';', '"', '\\');
}

fclose($output);
exit;
?>