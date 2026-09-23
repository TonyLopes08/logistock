<?php
session_start();
include_once '../inc/config.php';
include_once '../inc/validacoes.php';
include_once '../repositories/OperacaoJsonRepository.php';
include_once '../repositories/ProdutoRepository.php';
include_once '../inc/log_helper.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$perfis_permitidos = ['adm', 'supervisor', 'conferente'];
if (!in_array($usuario['perfil'], $perfis_permitidos)) {
    header('Location: login.php');
    exit;
}

$repoOp = new OperacaoJsonRepository();
$repoProduto = new ProdutoRepository();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$operacao = $repoOp->buscarPorId($id);

if (!$operacao) {
    header('Location: dashboard_conferente_v2.php');
    exit;
}

$mensagem = '';
$erro_item = '';

// --- PROCESSAR DADOS DO CONTAINER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'atualizar_container') {
    $container = strtoupper(trim($_POST['container'] ?? ''));
    $lacre = strtoupper(trim($_POST['lacre'] ?? ''));
    $navio = trim($_POST['navio'] ?? '');
    $armador = trim($_POST['armador'] ?? '');
    $tipo = $_POST['tipo_operacao'] ?? 'armazem';
    $mao_de_obra = $_POST['mao_de_obra'] ?? 'terminal';
    $tara = str_replace(',', '.', $_POST['tara'] ?? '');
    $vgm = str_replace(',', '.', $_POST['vgm'] ?? '');

    $erros = [];
    if (!validarContainer($container)) $erros[] = 'Container inválido. Use o padrão ISO: 4 letras maiúsculas + 7 números (ex: TCNU1763825).';
    if (!empty($navio) && !validarTexto($navio, 50)) $erros[] = 'Navio inválido (máx. 50 caracteres).';
    if (!empty($armador) && !validarTexto($armador, 50)) $erros[] = 'Armador inválido (máx. 50 caracteres).';
    if (!empty($lacre) && !validarLacre($lacre)) $erros[] = 'Lacre inválido (máx. 30 caracteres alfanuméricos).';
    if (!empty($tara) && !validarPeso($tara)) $erros[] = 'Tara inválida (máx. 2 casas decimais).';
    if (!empty($vgm) && !validarPeso($vgm)) $erros[] = 'VGM inválido (máx. 2 casas decimais).';

    if (count($erros) > 0) {
        $erro_item = implode('<br>', $erros);
    } else {
        $repoOp->atualizarDadosContainer($id, [
            'container' => $container,
            'lacre' => $lacre,
            'navio' => $navio,
            'armador' => $armador,
            'tipo_operacao' => $tipo,
            'mao_de_obra' => $mao_de_obra,
            'tara' => (float)$tara,
            'vgm' => (float)$vgm
        ], $usuario);
        $mensagem = "✅ Dados do container salvos!";
        $operacao = $repoOp->buscarPorId($id);
    }
}

// --- PROCESSAR ADIÇÃO DE ITEM ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'adicionar_item') {
    if ($operacao['status'] != 'Em Andamento') {
        $erro_item = '⚠️ Esta operação não está disponível para edição.';
    } else {
        if ($usuario['perfil'] == 'conferente' && $operacao['conferente_id'] != $usuario['id']) {
            $repoOp->registrarIngresso($id, $usuario);
            $operacao = $repoOp->buscarPorId($id);
        }

        $desc = trim($_POST['descricao'] ?? '');
        $qtd = (int)($_POST['quantidade'] ?? 0);
        $unidade = trim($_POST['unidade'] ?? '');
        $nf = trim($_POST['nota_fiscal'] ?? '');
        $peso_unit = str_replace(',', '.', $_POST['peso_unitario'] ?? 0);

        $erros = [];
        if (empty($desc) || !validarTexto($desc, 100)) $erros[] = 'Descrição inválida (máx. 100 caracteres).';
        if (!validarQuantidade($qtd)) $erros[] = 'Quantidade inválida (1 a 999.999).';
        if (!empty($nf) && !validarNotaFiscal($nf)) $erros[] = 'Nota fiscal inválida (máx. 20 caracteres, números e hífen).';
        if (!empty($peso_unit) && $peso_unit > 0 && !validarPeso($peso_unit)) $erros[] = 'Peso unitário inválido (máx. 2 casas decimais).';

        if (count($erros) > 0) {
            $erro_item = implode('<br>', $erros);
        } else {
            $repoOp->adicionarItem($id, [
                'descricao' => $desc,
                'quantidade' => $qtd,
                'unidade' => $unidade,
                'peso_unitario' => (float)$peso_unit,
                'nota_fiscal' => $nf
            ], $usuario);
            $mensagem = "✅ Item adicionado!";
            $operacao = $repoOp->buscarPorId($id);
        }
    }
}

// --- REMOÇÃO DE ITEM ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'remover_item') {
    $indice = (int)($_POST['item_indice'] ?? -1);
    if ($indice >= 0) {
        $repoOp->removerItem($id, $indice, $usuario);
        $mensagem = "🗑️ Item removido.";
        $operacao = $repoOp->buscarPorId($id);
    }
}

// --- ATUALIZAÇÃO DE STATUS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['item_indice']) && !isset($_POST['acao'])) {
    $indice = (int)$_POST['item_indice'];
    $status = $_POST['status'] ?? 'Conferido';
    $observacao = trim($_POST['observacao'] ?? '');
    $status_validos = ['Conferido', 'Divergência', 'Avariado', 'Pendente'];
    if (in_array($status, $status_validos)) {
        $repoOp->atualizarItem($id, $indice, $status, $observacao, $usuario);
        $mensagem = "✅ Item atualizado para <strong>$status</strong>!";
        $operacao = $repoOp->buscarPorId($id);
    }
}

$produtos_ativos = $repoProduto->listarAtivos();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Detalhes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Detalhes</h2>
        <div>
            <?php 
            $tem_notif = false;
            if ($usuario['perfil'] == 'conferente' && !empty($operacao['notificacao_para']) && in_array($usuario['id'], $operacao['notificacao_para'])) {
                $tem_notif = true;
            }
            if ($tem_notif): ?>
                <a href="#" class="notif-link" onclick="document.querySelector('.alerta-notificacao').scrollIntoView({behavior:'smooth'}); return false;">🔔 Notificação</a>
            <?php endif; ?>
            <span class="user-badge">👤 <?php echo htmlspecialchars($usuario['nome']); ?></span>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <div class="dashboard-content">

<?php 
$voltar_url = ($usuario['perfil'] == 'conferente') ? 'dashboard_conferente_v2.php' 
            : (($usuario['perfil'] == 'supervisor') ? 'dashboard_supervisor.php' : 'dashboard_adm.php');
?>
<a href="<?php echo $voltar_url; ?>" class="btn-voltar" title="Voltar para o Painel (Alt + ←)">
    <span class="icone">←</span>
    <span class="texto">Voltar ao Painel</span>
</a>
           
            <div class="header-op">
                <h2>📦 Booking <?php echo htmlspecialchars($operacao['numero_booking']); ?></h2>
                <div>
                    <?php 
                    $classe_status = 'status-aberta';
                    if ($operacao['status'] == 'Em Andamento') $classe_status = 'status-andamento';
                    if ($operacao['status'] == 'Finalizada') $classe_status = 'status-finalizada';
                    ?>
                    <span class="<?php echo $classe_status; ?>"><?php echo $operacao['status']; ?></span>
                </div>
            </div>

            <div class="info-op">
                <div><strong>Cliente</strong><span><?php echo htmlspecialchars($operacao['cliente_nome'] ?? '—'); ?></span></div>
                <div><strong>Mercadoria</strong><span><?php echo htmlspecialchars($operacao['tipo_mercadoria']); ?></span></div>
                <div><strong>Container</strong><span><?php echo htmlspecialchars($operacao['container'] ?? '—'); ?></span></div>
                <div><strong>Conferente</strong><span><?php echo htmlspecialchars($operacao['conferente_nome'] ?? '—'); ?></span></div>
                <div><strong>Estimado</strong><span><?php echo $operacao['quantidade_estimada']; ?> un.</span></div>
                <div><strong>Peso Total</strong><span><?php echo number_format($operacao['peso_bruto_total'], 2, ',', '.'); ?> kg</span></div>
                <div><strong>Início</strong><span><?php echo $operacao['data_inicio'] ?? '—'; ?></span></div>
                <?php if ($operacao['data_termino']): ?>
                    <div><strong>Término</strong><span><?php echo $operacao['data_termino']; ?></span></div>
                <?php endif; ?>
            </div>

            <?php 
            $qtd_real_total = 0;
            foreach (($operacao['itens'] ?? []) as $item) $qtd_real_total += (int)$item['quantidade'];
            $peso_real_total = (float)$operacao['peso_bruto_total'];
            $qtd_estimada = (int)($operacao['quantidade_estimada'] ?? 0);
            $peso_estimado = (float)($operacao['peso_estimado'] ?? 0);
            $diff_qtd = $qtd_real_total - $qtd_estimada;
            $diff_peso = $peso_real_total - $peso_estimado;
            $tem_itens = count($operacao['itens'] ?? []) > 0;
            ?>

            <?php if (!$tem_itens && $operacao['status'] == 'Em Andamento'): ?>
                <div style="background:#f0f4f8; border-left:4px solid #17a2b8; border-radius:8px; padding:15px; margin:20px 0;">
                    <strong style="color:#0c5460;">ℹ️ Operação em andamento</strong>
                    <p style="margin:5px 0 0; color:#0c5460; font-size:14px;">Adicione os itens da carga para que o sistema possa comparar o Booking com a conferência real.</p>
                </div>
            <?php endif; ?>

            <?php if ($tem_itens): ?>
                <div style="background:#fff; border:1px solid #e0e0e0; border-radius:10px; padding:20px; margin:20px 0;">
                    <h4 style="margin-top:0; color:#0b2b40;">📊 Comparação Booking vs Real</h4>
                    <table style="width:100%; border-collapse:collapse; font-size:14px;">
                        <thead>
                            <tr style="background:#0b2b40; color:white;">
                                <th style="padding:10px; text-align:left;">Item</th>
                                <th style="padding:10px; text-align:center;">Estimado</th>
                                <th style="padding:10px; text-align:center;">Real</th>
                                <th style="padding:10px; text-align:center;">Diferença</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding:10px;"><strong>Quantidade</strong></td>
                                <td style="padding:10px; text-align:center;"><?php echo $qtd_estimada; ?> un.</td>
                                <td style="padding:10px; text-align:center;"><?php echo $qtd_real_total; ?> un.</td>
                                <td style="padding:10px; text-align:center; font-weight:bold; color: <?php echo ($diff_qtd == 0) ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo ($diff_qtd == 0) ? '✔ Igual' : (($diff_qtd > 0 ? '+' : '') . $diff_qtd . ' un.'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px;"><strong>Peso</strong></td>
                                <td style="padding:10px; text-align:center;"><?php echo number_format($peso_estimado, 2, ',', '.'); ?> kg</td>
                                <td style="padding:10px; text-align:center;"><?php echo number_format($peso_real_total, 2, ',', '.'); ?> kg</td>
                                <td style="padding:10px; text-align:center; font-weight:bold; color: <?php echo (abs($diff_peso) < 0.01) ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo (abs($diff_peso) < 0.01) ? '✔ Igual' : (($diff_peso > 0 ? '+' : '') . number_format($diff_peso, 2, ',', '.') . ' kg'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php if ($diff_qtd != 0 || abs($diff_peso) >= 0.01): ?>
                        <div style="margin-top:15px; padding:12px; background:#fff3cd; border-left:4px solid #ffc107; border-radius:6px; font-size:13px; color:#856404;">
                            ⚠️ <strong>Atenção:</strong> Existem divergências entre o Booking e o Real.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($mensagem): ?><div class="mensagem"><?php echo $mensagem; ?></div><?php endif; ?>
            <?php if ($erro_item): ?><div class="erro"><?php echo $erro_item; ?></div><?php endif; ?>

            <!-- DADOS DO CONTAINER -->
            <?php if (in_array($usuario['perfil'], ['conferente', 'supervisor']) && $operacao['status'] == 'Em Andamento'): ?>
                <div class="form-container" style="margin: 20px 0; border-left: 5px solid #0b2b40;">
                    <h4 style="margin-top:0; color:#0b2b40;">🚢 Dados do Container</h4>
                    <p style="color:#666; font-size:14px; margin-top:0;">Preencha os dados do container antes de adicionar os itens.</p>

                    <form method="POST" action="">
                        <input type="hidden" name="acao" value="atualizar_container">
                        <div class="form-row">
                            <div>
                                <label>Cliente</label>
                                <input type="text" value="<?php echo htmlspecialchars($operacao['cliente_nome'] ?? '—'); ?>" disabled>
                            </div>
                            <div>
                                <label>Booking</label>
                                <input type="text" value="<?php echo htmlspecialchars($operacao['numero_booking'] ?? '—'); ?>" disabled>
                            </div>
                            <div>
                                <label for="container">Nº Container *</label>
                                <input type="text" id="container" name="container" value="<?php echo htmlspecialchars($operacao['container'] ?? ''); ?>" placeholder="Ex: TCNU1763825" required maxlength="12" title="Formato ISO: 4 letras maiúsculas + 7 números (ex: TCNU1763825)">
                            </div>
                            <div>
                                <label for="lacre">Lacre</label>
                                <input type="text" id="lacre" name="lacre" value="<?php echo htmlspecialchars($operacao['lacre'] ?? ''); ?>" placeholder="Ex: HUK035870" maxlength="30">
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label for="navio">M/V (Navio)</label>
                                <input type="text" id="navio" name="navio" value="<?php echo htmlspecialchars($operacao['navio'] ?? ''); ?>" placeholder="Ex: MSC MICHELIN" maxlength="50">
                            </div>
                            <div>
                                <label for="armador">Armador</label>
                                <input type="text" id="armador" name="armador" value="<?php echo htmlspecialchars($operacao['armador'] ?? ''); ?>" placeholder="Ex: Maersk, MSC, CMA CGM" maxlength="50">
                            </div>
                            <div>
                                <label for="tipo_operacao">Tipo de Operação</label>
                                <select id="tipo_operacao" name="tipo_operacao">
                                    <option value="armazem" <?php echo ($operacao['tipo_operacao'] ?? '') == 'armazem' ? 'selected' : ''; ?>>Armazém</option>
                                    <option value="cross_docking" <?php echo ($operacao['tipo_operacao'] ?? '') == 'cross_docking' ? 'selected' : ''; ?>>Cross-Docking</option>
                                    <option value="vagao_az_cntr" <?php echo ($operacao['tipo_operacao'] ?? '') == 'vagao_az_cntr' ? 'selected' : ''; ?>>Vagão-AZ-CNTR</option>
                                    <option value="carregamento" <?php echo ($operacao['tipo_operacao'] ?? '') == 'carregamento' ? 'selected' : ''; ?>>Carregamento</option>
                                    <option value="descarregamento" <?php echo ($operacao['tipo_operacao'] ?? '') == 'descarregamento' ? 'selected' : ''; ?>>Descarregamento</option>
                                </select>
                            </div>
                            <div>
                                <label for="mao_de_obra">Mão de Obra</label>
                                <select id="mao_de_obra" name="mao_de_obra">
                                    <option value="terminal" <?php echo ($operacao['mao_de_obra'] ?? '') == 'terminal' ? 'selected' : ''; ?>>Terminal (própria)</option>
                                    <option value="sindicato" <?php echo ($operacao['mao_de_obra'] ?? '') == 'sindicato' ? 'selected' : ''; ?>>Sindicato</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label for="tara">Tara (kg)</label>
                                <input type="text" id="tara" name="tara" value="<?php echo htmlspecialchars($operacao['tara'] ?? ''); ?>" placeholder="Ex: 2230" pattern="[0-9]+([,.][0-9]+)?">
                            </div>
                            <div>
                                <label for="vgm">VGM (kg) - Peso Bruto Verificado</label>
                                <input type="text" id="vgm" name="vgm" value="<?php echo htmlspecialchars($operacao['vgm'] ?? ''); ?>" placeholder="Ex: 27000" pattern="[0-9]+([,.][0-9]+)?">
                            </div>
                        </div>
                        <button type="submit" style="background:#0b2b40;">💾 Salvar Dados do Container</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ADICIONAR ITEM -->
            <?php if (in_array($usuario['perfil'], ['conferente', 'supervisor']) && $operacao['status'] == 'Em Andamento'): ?>
                <div class="form-item" style="background:#f0f4f8; padding:20px; border-radius:8px; margin:20px 0; border:2px dashed #0b2b40;">
                    <h4 style="margin-top:0;">➕ Adicionar Item</h4>
                    <p style="color:#666; font-size:13px; margin-top:0;">Digite a descrição e escolha um produto cadastrado — o sistema preenche a unidade automaticamente.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="acao" value="adicionar_item">
                        <input type="hidden" id="peso_unitario" name="peso_unitario" value="0">
                        <div class="form-row">
                            <div style="position:relative; flex:2;">
                                <label for="descricao">Descrição *</label>
                                <input type="text" id="descricao" name="descricao" placeholder="Digite para buscar..." required maxlength="100" autocomplete="off">
                                <div id="sugestoes-produtos" style="display:none; position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #ccc; border-radius:6px; max-height:200px; overflow-y:auto; z-index:100; box-shadow:0 4px 12px rgba(0,0,0,0.15);"></div>
                            </div>
                            <div>
                                <label for="quantidade">Quantidade</label>
                                <input type="number" id="quantidade" name="quantidade" value="1" step="1" min="1" max="999999" required>
                            </div>
                            <div>
                                <label for="unidade">Unidade</label>
                                <input type="text" id="unidade" name="unidade" placeholder="Ex: sacas 50kg" maxlength="30">
                            </div>
                            <div>
                                <label for="nota_fiscal">Nota Fiscal</label>
                                <input type="text" id="nota_fiscal" name="nota_fiscal" placeholder="Ex: 38638-340" pattern="[0-9\-]+" maxlength="20">
                            </div>
                        </div>
                        <button type="submit" style="background:#28a745; color:white; border:none; border-radius:4px; padding:8px 16px; cursor:pointer; font-weight:bold; margin-top:10px;">➕ Adicionar Item</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- LISTA DE ITENS -->
            <h3 style="margin-top:20px; color:#0b2b40;">📋 Itens da Carga</h3>

            <?php foreach (($operacao['itens'] ?? []) as $indice => $item): 
                $status_item = $item['status_conferencia'] ?? 'Pendente';
                $classe_linha = 'pendente';
                if ($status_item == 'Conferido') $classe_linha = 'conferido';
                if ($status_item == 'Divergência') $classe_linha = 'divergencia';
                if ($status_item == 'Avariado') $classe_linha = 'avariado';
            ?>
                <div class="item-linha <?php echo $classe_linha; ?>" style="display:flex; align-items:center; justify-content:space-between; padding:10px 15px; background:#f9f9f9; border-radius:8px; margin-bottom:8px; border-left:6px solid #ccc; flex-wrap:wrap; gap:10px;">
                    <div style="flex:2; min-width:200px;">
                        <strong><?php echo htmlspecialchars($item['descricao']); ?></strong>
                        <small style="color:#666; display:block;">
                            Qtd: <?php echo $item['quantidade']; ?> <?php echo htmlspecialchars($item['unidade']); ?> | 
                            NF: <?php echo htmlspecialchars($item['nota_fiscal'] ?? 'N/A'); ?>
                        </small>
                        <?php if ($status_item != 'Pendente' && !empty($item['observacao'])): ?>
                            <small style="color:#dc3545; display:block;"><strong>Obs:</strong> <?php echo htmlspecialchars($item['observacao']); ?></small>
                        <?php endif; ?>
                        <small style="display:block;"><strong>Status:</strong> <?php echo $status_item; ?></small>
                    </div>
                    <div style="display:flex; gap:5px; flex-wrap:wrap;">
                        <?php if ($operacao['status'] == 'Em Andamento'): ?>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="item_indice" value="<?php echo $indice; ?>">
                                <input type="hidden" name="status" value="Conferido">
                                <button type="submit" class="btn-acao" style="background:#28a745;">✅ OK</button>
                            </form>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Indica DIVERGÊNCIA. Confirma?')">
                                <input type="hidden" name="item_indice" value="<?php echo $indice; ?>">
                                <input type="hidden" name="status" value="Divergência">
                                <button type="submit" class="btn-acao" style="background:#fd7e14;">⚠️ Divergência</button>
                            </form>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Indica AVARIADO. Confirma?')">
                                <input type="hidden" name="item_indice" value="<?php echo $indice; ?>">
                                <input type="hidden" name="status" value="Avariado">
                                <button type="submit" class="btn-acao" style="background:#dc3545;">💥 Avariado</button>
                            </form>
                            <?php if ($status_item != 'Pendente'): ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="item_indice" value="<?php echo $indice; ?>">
                                    <input type="hidden" name="status" value="Pendente">
                                    <button type="submit" class="btn-acao" style="background:#ffc107; color:#333;">↩️ Desfazer</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($status_item == 'Pendente'): ?>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Remover este item?')">
                                    <input type="hidden" name="acao" value="remover_item">
                                    <input type="hidden" name="item_indice" value="<?php echo $indice; ?>">
                                    <button type="submit" class="btn-acao" style="background:#dc3545;">🗑️</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#28a745; font-weight:bold;">✔ Finalizado</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php 
            $sou_conferente_responsavel = ($usuario['perfil'] == 'conferente' && $operacao['conferente_id'] == $usuario['id']);
            $tem_notificacao = !empty($operacao['reportado_pelo_adm']) && !empty($operacao['notificacao_para']) && in_array($usuario['id'], $operacao['notificacao_para']);
            ?>
            <?php if ($tem_notificacao && $sou_conferente_responsavel): ?>
                <div class="alerta-notificacao" style="margin-top: 20px;">
                    <strong style="color:#856404;">⚠️ O ADM reportou um erro nesta operação!</strong>
                    <p style="color:#856404;"><strong>Tipo:</strong> <?php echo htmlspecialchars($operacao['tipo_erro']); ?></p>
                    <p style="color:#856404;"><strong>Motivo:</strong> <?php echo htmlspecialchars($operacao['motivo_reporte']); ?></p>
                    <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="reabrir_operacao.php?id=<?php echo $operacao['id']; ?>" class="btn-secondary" style="background:#0b2b40;" onclick="return confirm('Reabrir esta operação?')">🔓 Reabrir Operação</a>
                        <a href="justificar_operacao.php?id=<?php echo $operacao['id']; ?>" class="btn-secondary" style="background:#28a745;">💬 Justificar (Está Correto)</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php 
            $todos_conferidos = true;
            $tem_pendencias = false;
            foreach (($operacao['itens'] ?? []) as $item) {
                if ($item['status_conferencia'] == 'Pendente') {
                    $todos_conferidos = false;
                    $tem_pendencias = true;
                }
            }
            $tem_itens = count($operacao['itens'] ?? []) > 0;
            ?>

            <?php if ($usuario['perfil'] == 'conferente' && $operacao['status'] == 'Em Andamento'): ?>
                <?php if (!$tem_itens): ?>
                    <div class="erro" style="margin-top:20px;">⚠️ Adicione pelo menos <strong>1 item</strong> antes de finalizar a operação.</div>
                <?php else: ?>
                    <form method="POST" action="finalizar_operacao.php" onsubmit="return confirm('<?php echo $tem_pendencias ? "Existem itens pendentes! Deseja finalizar mesmo assim?" : "Finalizar esta operação?" ?>')">
                        <input type="hidden" name="id_operacao" value="<?php echo $operacao['id']; ?>">
                        <?php if ($tem_pendencias): ?><input type="hidden" name="tem_pendencias" value="1"><?php endif; ?>
                        <button type="submit" style="background:#0b2b40; color:white; padding:14px; border:none; border-radius:8px; font-weight:bold; width:100%; font-size:18px; cursor:pointer; margin-top:20px;">✔ Finalizar Operação</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($operacao['log'])): ?>
                <h3 style="margin-top:30px; color:#0b2b40;">📜 Histórico de Ações</h3>
                <div style="background:#f8f9fa; padding:15px; border-radius:8px;">
                    <?php echo formatarLog($operacao['log']); ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        const produtos = <?php echo json_encode($produtos_ativos); ?>;
        const inputDesc = document.getElementById('descricao');
        const sugestoes = document.getElementById('sugestoes-produtos');
        const inputUnidade = document.getElementById('unidade');
        const inputPeso = document.getElementById('peso_unitario');

        if (inputDesc) {
            inputDesc.addEventListener('input', function() {
                const termo = this.value.toLowerCase().trim();
                if (termo.length < 2) { sugestoes.style.display = 'none'; return; }
                const filtrados = produtos.filter(p => p.nome.toLowerCase().includes(termo));
                if (filtrados.length === 0) { sugestoes.style.display = 'none'; return; }
                sugestoes.innerHTML = '';
                filtrados.slice(0, 8).forEach(p => {
                    const div = document.createElement('div');
                    div.style.cssText = 'padding:10px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; font-size:14px;';
                    div.innerHTML = `<strong>${p.nome}</strong><br><small style="color:#666;">${p.categoria} • ${p.unidade_padrao}</small>`;
                    div.addEventListener('mouseenter', () => div.style.background = '#f0f4f8');
                    div.addEventListener('mouseleave', () => div.style.background = 'white');
                    div.addEventListener('click', () => {
                        inputDesc.value = p.nome;
                        if (inputUnidade) inputUnidade.value = p.unidade_padrao;
                        if (inputPeso) inputPeso.value = p.peso_unitario_padrao || 0;
                        sugestoes.style.display = 'none';
                    });
                    sugestoes.appendChild(div);
                });
                sugestoes.style.display = 'block';
            });
            inputDesc.addEventListener('blur', function() {
                setTimeout(() => { sugestoes.style.display = 'none'; }, 200);
            });
        }

        const inputContainer = document.getElementById('container');
        if (inputContainer) {
            inputContainer.addEventListener('input', function(e) {
                let v = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                v = v.substring(0, 11);
                let parte1 = v.substring(0, 4).replace(/[^A-Z]/g, '');
                let parte2 = v.substring(4).replace(/[^0-9]/g, '');
                v = parte1 + parte2;
                if (v.length === 11) v = v.substring(0, 10) + '-' + v.substring(10);
                e.target.value = v;
            });
        }

        const inputLacre = document.getElementById('lacre');
        if (inputLacre) {
            inputLacre.addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
            });
        }

        ['navio', 'armador'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });
        });
    </script>

    <script src="../assets/js/validacao-tempo-real.js"></script>
    <script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>

<?php if ($mensagem): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        mostrarToast(<?php echo json_encode(strip_tags($mensagem)); ?>, 'sucesso');
    });
</script>
<?php endif; ?>

<?php if ($erro_item): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        mostrarToast(<?php echo json_encode(strip_tags($erro_item)); ?>, 'erro', 5000);
    });
</script>
<?php endif; ?>
    
</body>
</html>