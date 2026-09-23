<?php
// Desabilita avisos para evitar corrupção do PDF
error_reporting(E_ERROR | E_PARSE);

// Inclui a biblioteca FPDF
require_once '../inc/fpdf/fpdf.php';

// Inclui o repositório
include_once '../inc/config.php';
include_once '../repositories/OperacaoJsonRepository.php';
include_once '../repositories/UsuarioRepository.php';

// Pega o ID da operação
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$repoOp = new OperacaoJsonRepository();
$repoUser = new UsuarioRepository();
$operacao = $repoOp->buscarPorId($id);

if (!$operacao) {
    die('Erro: Operação não encontrada.');
}

// Busca dados do conferente
$conferente = null;
if (!empty($operacao['finalizada_por'])) {
    $conferente = $repoUser->buscarPorId($operacao['finalizada_por']);
}

// ==========================================
// FUNÇÃO PARA CONVERTER TEXTO (encoding seguro)
// ==========================================
function txt($str) {
    if ($str === null) $str = '';
    $str = (string)$str;
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
    }
    if (function_exists('iconv')) {
        $r = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
        return ($r !== false) ? $r : $str;
    }
    return @utf8_decode($str);
}

// ==========================================
// FUNÇÃO PARA FORMATAR CPF
// ==========================================
function formatarCpfLocal($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return $cpf;
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// ==========================================
// CRIA O PDF
// ==========================================
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);

// ==========================================
// 1. CABEÇALHO COM LOGO
// ==========================================
// Procura pela logo (jpg primeiro, depois png)
$caminho_logo = null;
if (file_exists(__DIR__ . '/../assets/img/logo.jpg')) {
    $caminho_logo = __DIR__ . '/../assets/img/logo.jpg';
} elseif (file_exists(__DIR__ . '/../assets/img/logo.jpeg')) {
    $caminho_logo = __DIR__ . '/../assets/img/logo.jpeg';
} elseif (file_exists(__DIR__ . '/../assets/img/logo.png')) {
    $caminho_logo = __DIR__ . '/../assets/img/logo.png';
}

// Só coloca a imagem se o arquivo existir e for válido
if ($caminho_logo) {
    $info = @getimagesize($caminho_logo);
    $tipo_real = $info['mime'] ?? '';
    
    // JPG é sempre aceito. PNG só é aceito se não tiver transparência.
    if ($tipo_real == 'image/jpeg') {
        $pdf->Image($caminho_logo, 10, 10, 30);
    } elseif ($tipo_real == 'image/png') {
        // Tenta colocar o PNG, mas se der erro, ignora
        try {
            $pdf->Image($caminho_logo, 10, 10, 30);
        } catch (Exception $e) {
            // Ignora silenciosamente
        }
    }
}

// Título ao lado da logo
$pdf->SetXY(45, 12);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 8, txt('PACKING LIST - CONFERÊNCIA DE CARGA'), 0, 1, 'L');

$pdf->SetX(45);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, txt('Sulterminais - Armazéns Gerais'), 0, 1, 'L');

$pdf->SetX(45);
$pdf->Cell(0, 5, txt('Relatório gerado em: ' . date('d/m/Y H:i:s')), 0, 1, 'L');

$pdf->Ln(10);

// Linha divisória
$pdf->SetDrawColor(11, 43, 64);
$pdf->SetLineWidth(0.5);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

// ==========================================
// 2. DADOS DO BOOKING (Supervisor)
// ==========================================
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(11, 43, 64);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(190, 8, txt(' DADOS DO BOOKING (Estimado pelo Supervisor)'), 1, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 7, txt('Nº Booking:'), 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(70, 7, txt($operacao['numero_booking'] ?? '—'), 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(35, 7, txt('Cliente:'), 1, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(35, 7, txt($operacao['cliente_nome'] ?? '—'), 1, 1, 'L');
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 7, txt('Tipo de Mercadoria:'), 1, 0, 'L');
$pdf->Cell(70, 7, txt($operacao['tipo_mercadoria'] ?? '—'), 1, 0, 'L');
$pdf->Cell(35, 7, txt('Qtd. Estimada:'), 1, 0, 'L');
$pdf->Cell(35, 7, txt(number_format($operacao['quantidade_estimada'] ?? 0, 0, ',', '.')), 1, 1, 'L');

$pdf->Cell(50, 7, txt('Peso Estimado:'), 1, 0, 'L');
$pdf->Cell(70, 7, txt(number_format($operacao['peso_estimado'] ?? 0, 2, ',', '.') . ' kg'), 1, 0, 'L');
$pdf->Cell(35, 7, txt('Data Prevista:'), 1, 0, 'L');
$pdf->Cell(35, 7, txt($operacao['data_prevista'] ?? '—'), 1, 1, 'L');

$pdf->Cell(50, 7, txt('Criado por:'), 1, 0, 'L');
$pdf->Cell(140, 7, txt(($operacao['criado_por_nome'] ?? '—') . ' em ' . ($operacao['data_criacao'] ?? '—')), 1, 1, 'L');

if (!empty($operacao['observacoes_booking'])) {
    $pdf->Cell(50, 7, txt('Observações:'), 1, 0, 'L');
    $pdf->MultiCell(140, 7, txt($operacao['observacoes_booking']), 1, 'L');
}

$pdf->Ln(3);

// ==========================================
// 3. DADOS REAIS (Conferente)
// ==========================================
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(26, 75, 102);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(190, 8, txt(' DADOS REAIS (Conferido pelo Operador)'), 1, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 7, txt('Container:'), 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(140, 7, txt($operacao['container'] ?? '—'), 1, 1, 'L');
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 7, txt('Lacre:'), 1, 0, 'L');
$pdf->Cell(70, 7, txt($operacao['lacre'] ?? '—'), 1, 0, 'L');
$pdf->Cell(35, 7, txt('M/V (Navio):'), 1, 0, 'L');
$pdf->Cell(35, 7, txt($operacao['navio'] ?? '—'), 1, 1, 'L');

$pdf->Cell(50, 7, txt('Armador:'), 1, 0, 'L');
$pdf->Cell(70, 7, txt($operacao['armador'] ?? '—'), 1, 0, 'L');

// Tipo de Operação por extenso
$tipos = [
    'armazem' => 'Armazém',
    'cross_docking' => 'Cross-Docking',
    'vagao_az_cntr' => 'Vagão-AZ-CNTR',
    'carregamento' => 'Carregamento',
    'descarregamento' => 'Descarregamento'
];
$tipo_exibicao = $tipos[$operacao['tipo_operacao'] ?? 'armazem'] ?? ($operacao['tipo_operacao'] ?? '—');

$pdf->Cell(35, 7, txt('Tipo Operação:'), 1, 0, 'L');
$pdf->Cell(35, 7, txt($tipo_exibicao), 1, 1, 'L');

$pdf->Cell(50, 7, txt('Mão de Obra:'), 1, 0, 'L');
$pdf->Cell(70, 7, txt(($operacao['mao_de_obra'] ?? 'terminal') == 'sindicato' ? 'Sindicato' : 'Terminal'), 1, 0, 'L');
$pdf->Cell(35, 7, txt('Tara:'), 1, 0, 'L');
$pdf->Cell(35, 7, txt(number_format($operacao['tara'] ?? 0, 2, ',', '.') . ' kg'), 1, 1, 'L');

$pdf->Cell(50, 7, txt('VGM:'), 1, 0, 'L');
$pdf->Cell(70, 7, txt(number_format($operacao['vgm'] ?? 0, 2, ',', '.') . ' kg'), 1, 0, 'L');
$pdf->Cell(35, 7, txt('Peso Carga:'), 1, 0, 'L');
$pdf->Cell(35, 7, txt(number_format($operacao['peso_bruto_total'] ?? 0, 2, ',', '.') . ' kg'), 1, 1, 'L');

$pdf->Cell(50, 7, txt('Início:'), 1, 0, 'L');
$pdf->Cell(70, 7, txt($operacao['data_inicio'] ?? '—'), 1, 0, 'L');
$pdf->Cell(35, 7, txt('Término:'), 1, 0, 'L');
$pdf->Cell(35, 7, txt($operacao['data_termino'] ?? '—'), 1, 1, 'L');

$pdf->Cell(50, 7, txt('Status:'), 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(140, 7, txt($operacao['status']), 1, 1, 'L');
$pdf->SetFont('Arial', '', 10);

$pdf->Ln(3);

// ==========================================
// 4. COMPARATIVO BOOKING vs REAL
// ==========================================
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(255, 193, 7);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(190, 8, txt(' ANÁLISE DE DIVERGÊNCIAS'), 1, 1, 'L', true);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(95, 7, txt('Estimado pelo Booking'), 1, 0, 'C', true);
$pdf->Cell(95, 7, txt('Registrado pelo Conferente'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$qtd_est = (int)($operacao['quantidade_estimada'] ?? 0);
$qtd_real = 0;
foreach (($operacao['itens'] ?? []) as $item) {
    $qtd_real += (int)$item['quantidade'];
}
$peso_est = (float)($operacao['peso_estimado'] ?? 0);
$peso_real = (float)($operacao['peso_bruto_total'] ?? 0);

// Quantidade
$pdf->Cell(95, 7, txt('Quantidade: ' . number_format($qtd_est, 0, ',', '.')), 1, 0, 'C');
$diff_qtd = $qtd_real - $qtd_est;
$cor_qtd = ($diff_qtd == 0) ? 'texto-verde' : 'texto-vermelho';
$texto_qtd = 'Quantidade: ' . number_format($qtd_real, 0, ',', '.');
if ($diff_qtd != 0) {
    $texto_qtd .= ' (' . ($diff_qtd > 0 ? '+' : '') . $diff_qtd . ')';
    $pdf->SetTextColor(200, 0, 0);
} else {
    $pdf->SetTextColor(0, 150, 0);
}
$pdf->Cell(95, 7, txt($texto_qtd), 1, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

// Peso
$pdf->Cell(95, 7, txt('Peso: ' . number_format($peso_est, 2, ',', '.') . ' kg'), 1, 0, 'C');
$diff_peso = $peso_real - $peso_est;
$texto_peso = 'Peso: ' . number_format($peso_real, 2, ',', '.') . ' kg';
if (abs($diff_peso) > 0.01) {
    $texto_peso .= ' (' . ($diff_peso > 0 ? '+' : '') . number_format($diff_peso, 2, ',', '.') . ' kg)';
    $pdf->SetTextColor(200, 0, 0);
} else {
    $pdf->SetTextColor(0, 150, 0);
}
$pdf->Cell(95, 7, txt($texto_peso), 1, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

$pdf->Ln(3);

// ==========================================
// 5. ITENS DA CARGA
// ==========================================
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(11, 43, 64);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(190, 8, txt(' ITENS DA CARGA'), 1, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(70, 7, txt('Descrição'), 1, 0, 'C', true);
$pdf->Cell(20, 7, txt('Qtd'), 1, 0, 'C', true);
$pdf->Cell(30, 7, txt('Unidade'), 1, 0, 'C', true);
$pdf->Cell(35, 7, txt('Nota Fiscal'), 1, 0, 'C', true);
$pdf->Cell(35, 7, txt('Status'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 8);
$itens = $operacao['itens'] ?? [];
if (count($itens) > 0) {
    foreach ($itens as $item) {
    $pdf->Cell(70, 6, txt(substr($item['descricao'] ?? '', 0, 35)), 1, 0, 'L');
    $pdf->Cell(20, 6, txt($item['quantidade'] ?? 0), 1, 0, 'C');
    $pdf->Cell(30, 6, txt(substr($item['unidade'] ?? '', 0, 15)), 1, 0, 'C');
    $pdf->Cell(35, 6, txt($item['nota_fiscal'] ?? '—'), 1, 0, 'C');
    
    $status_item = $item['status_conferencia'] ?? 'Pendente';
    if ($status_item == 'Divergência' || $status_item == 'Avariado') {
        $pdf->SetTextColor(200, 0, 0);
    } elseif ($status_item == 'Conferido') {
        $pdf->SetTextColor(0, 150, 0);
    }
    $pdf->Cell(35, 6, txt($status_item), 1, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
}
} else {
    $pdf->Cell(190, 7, txt('Nenhum item registrado.'), 1, 1, 'C');
}

$pdf->Ln(3);

// ==========================================
// 6. AVARIAS E OBSERVAÇÕES
// ==========================================
$observacoes = [];
foreach ($itens as $item) {
    if (!empty($item['observacao'])) {
        $observacoes[] = $item['descricao'] . ': ' . $item['observacao'];
    }
}

if (count($observacoes) > 0) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(255, 243, 205);
    $pdf->Cell(190, 7, txt(' AVARIAS / OBSERVAÇÕES'), 1, 1, 'L', true);
    $pdf->SetFont('Arial', '', 9);
    foreach ($observacoes as $obs) {
        $pdf->MultiCell(190, 6, txt('• ' . $obs), 1, 'L');
    }
    $pdf->Ln(3);
}


// ==========================================
// 6.5. INFORMAÇÕES FINAIS (INÍCIO, TÉRMINO, CONFERENTES)
// ==========================================
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(11, 43, 64);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(190, 8, txt(' INFORMAÇÕES FINAIS DA OPERAÇÃO'), 1, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

// Início e Término
$pdf->Cell(50, 7, txt('Início da Operação:'), 1, 0, 'L');
$pdf->Cell(60, 7, txt($operacao['data_inicio'] ?? '—'), 1, 0, 'L');
$pdf->Cell(30, 7, txt('Término:'), 1, 0, 'L');
$pdf->Cell(50, 7, txt($operacao['data_termino'] ?? 'Em andamento'), 1, 1, 'L');

// Conferentes (nome de quem pegou e quem finalizou)
$pdf->Cell(50, 7, txt('Conferente (Pegou):'), 1, 0, 'L');
$pdf->Cell(140, 7, txt($operacao['conferente_nome'] ?? '—'), 1, 1, 'L');

$pdf->Cell(50, 7, txt('Conferente (Finalizou):'), 1, 0, 'L');
$pdf->Cell(140, 7, txt($operacao['finalizada_por_nome'] ?? '—'), 1, 1, 'L');

$pdf->Ln(3);

// ==========================================
// 7. STATUS DO ADM (Se reportou ou justificou)
// ==========================================
if (!empty($operacao['reportado_pelo_adm'])) {
    $pdf->SetFont('Arial', 'B', 10);
    
    if (!empty($operacao['justificado_pelo_conferente'])) {
        // Conferente justificou
        $pdf->SetFillColor(231, 243, 255);
        $pdf->Cell(190, 7, txt(' STATUS DO ADM: Erro reportado, mas o conferente JUSTIFICOU'), 1, 1, 'L', true);
        $pdf->SetFont('Arial', '', 9);
        $pdf->MultiCell(190, 6, txt('Motivo do reporte: ' . ($operacao['motivo_reporte'] ?? '—')), 1, 'L');
        $pdf->MultiCell(190, 6, txt('Justificativa do conferente: ' . ($operacao['justificativa_conferente'] ?? '—')), 1, 'L');
    } else {
        // Aguardando resposta
        $pdf->SetFillColor(255, 243, 205);
        $pdf->Cell(190, 7, txt(' STATUS DO ADM: Erro reportado (aguardando resposta do conferente)'), 1, 1, 'L', true);
        $pdf->SetFont('Arial', '', 9);
        $pdf->MultiCell(190, 6, txt('Tipo: ' . ($operacao['tipo_erro'] ?? '—') . ' | Motivo: ' . ($operacao['motivo_reporte'] ?? '—')), 1, 'L');
    }
    $pdf->Ln(5);
}

// ==========================================
// 8. ASSINATURA DO CONFERENTE
// ==========================================
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(190, 7, txt('ASSINATURA DO CONFERENTE RESPONSÁVEL'), 0, 1, 'C');
$pdf->Ln(15);

$pdf->SetDrawColor(0, 0, 0);
$pdf->Line(60, $pdf->GetY(), 150, $pdf->GetY());
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 10);
$nome_conf = $conferente['nome'] ?? ($operacao['conferente_nome'] ?? '—');
$cpf_conf = $conferente['cpf'] ?? '';
$pdf->Cell(190, 6, txt($nome_conf), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(190, 5, txt('CPF: ' . formatarCpfLocal($cpf_conf)), 0, 1, 'C');

$pdf->Ln(10);

// ==========================================
// 9. RODAPÉ
// ==========================================
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(190, 5, txt('LogiStock v3.0 - Sistema de Conferência Portuária - Sulterminais'), 0, 1, 'C');
$pdf->Cell(190, 5, txt('Documento gerado em ' . date('d/m/Y \à\s H:i:s')), 0, 1, 'C');

// ==========================================
// SAÍDA
// ==========================================
$pdf->Output('I', 'PackingList_' . ($operacao['numero_booking'] ?? 'sem_booking') . '_' . date('Ymd_His') . '.pdf');
exit;
?>