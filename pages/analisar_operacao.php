<?php
session_start();
include_once '../inc/config.php';
include_once '../inc/log_helper.php';
include_once '../inc/validacoes.php';
include_once '../repositories/OperacaoJsonRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'adm') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repoOp = new OperacaoJsonRepository();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$operacao = $repoOp->buscarPorId($id);

if (!$operacao) {
    header('Location: dashboard_adm.php');
    exit;
}

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'reportar') {
    $motivo = trim($_POST['motivo'] ?? '');
    $tipo_erro = $_POST['tipo_erro'] ?? '';

    $erros = [];
    if (empty($motivo) || strlen($motivo) < 5) $erros[] = 'Motivo muito curto (mín. 5 caracteres).';
    if (!in_array($tipo_erro, ['Peso errado', 'Item faltando', 'Item sobrando', 'Avarias não registradas', 'Dados incorretos', 'Outro'])) {
        $erros[] = 'Selecione um tipo de erro válido.';
    }

    if (count($erros) > 0) {
        $erro = implode('<br>', $erros);
    } else {
        $repoOp->reportarErro($id, $usuario, $motivo, $tipo_erro);
        $mensagem = "⚠️ Erro reportado! O conferente foi notificado.";
        $operacao = $repoOp->buscarPorId($id);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'reportar_novamente') {
    $motivo = trim($_POST['motivo'] ?? '');
    $tipo_erro = $_POST['tipo_erro'] ?? '';

    $erros = [];
    if (empty($motivo) || strlen($motivo) < 5) $erros[] = 'Motivo muito curto (mín. 5 caracteres).';
    if (!in_array($tipo_erro, ['Peso errado', 'Item faltando', 'Item sobrando', 'Avarias não registradas', 'Dados incorretos', 'Outro'])) {
        $erros[] = 'Selecione um tipo de erro válido.';
    }

    if (count($erros) > 0) {
        $erro = implode('<br>', $erros);
    } else {
        $repoOp->reportarErroNovamente($id, $usuario, $motivo, $tipo_erro);
        $mensagem = "🔁 Reporte enviado novamente! O conferente foi notificado.";
        $operacao = $repoOp->buscarPorId($id);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Analisar Operação</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Analisar Operação</h2>
        <div>
            <span class="user-badge">👤 <?php echo htmlspecialchars($usuario['nome']); ?></span>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <div class="dashboard-content">

            <a href="dashboard_adm.php" class="btn-voltar" title="Voltar para o Painel Administrativo (Alt + ←)">
                <span class="icone">←</span>
                <span class="texto">Voltar ao Painel</span>
            </a>
            
            <?php if ($mensagem): ?>
                <div class="mensagem"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            <?php if ($erro): ?>
                <div class="erro"><?php echo $erro; ?></div>
            <?php endif; ?>

            <h3 style="color:#0b2b40;">📦 Booking <?php echo htmlspecialchars($operacao['numero_booking']); ?></h3>
            
            <div class="info-op">
                <div><strong>Mercadoria</strong><span><?php echo htmlspecialchars($operacao['tipo_mercadoria']); ?></span></div>
                <div><strong>Qtd. Estimada</strong><span><?php echo $operacao['quantidade_estimada']; ?> un.</span></div>
                <div><strong>Peso Estimado</strong><span><?php echo number_format($operacao['peso_estimado'], 2, ',', '.'); ?> kg</span></div>
                <div><strong>Peso Real</strong><span><?php echo number_format($operacao['peso_bruto_total'], 2, ',', '.'); ?> kg</span></div>
                <div><strong>Container</strong><span><?php echo htmlspecialchars($operacao['container'] ?? '—'); ?></span></div>
                <div><strong>Lacre</strong><span><?php echo htmlspecialchars($operacao['lacre'] ?? '—'); ?></span></div>
                <div><strong>Conferente</strong><span><?php echo htmlspecialchars($operacao['finalizada_por_nome'] ?? '—'); ?></span></div>
                <div><strong>Término</strong><span><?php echo $operacao['data_termino']; ?></span></div>
            </div>

            <h4>📋 Itens da Carga</h4>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Descrição</th>
                            <th>Qtd</th>
                            <th>Peso Unit.</th>
                            <th>Peso Total</th>
                            <th>Status</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operacao['itens'] as $i => $item): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                            <td><?php echo $item['quantidade']; ?> <?php echo htmlspecialchars($item['unidade']); ?></td>
                            <td><?php echo number_format($item['peso_unitario'], 2, ',', '.'); ?> kg</td>
                            <td><?php echo number_format($item['peso_total'], 2, ',', '.'); ?> kg</td>
                            <td><?php echo $item['status_conferencia']; ?></td>
                            <td><?php echo htmlspecialchars($item['observacao'] ?: '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h4 style="margin-top:30px;">📜 Histórico de Ações</h4>
            <div style="background:#f8f9fa; padding:15px; border-radius:8px;">
                <?php echo formatarLog($operacao['log'] ?? []); ?>
            </div>

            <?php if (empty($operacao['reportado_pelo_adm'])): ?>
                <div class="form-container" style="margin-top:30px; border-left:5px solid #dc3545;">
                    <h4 style="color:#dc3545; margin-top:0;">⚠️ Reportar Erro nesta Operação</h4>
                    <p style="color:#666; font-size:14px;">Se algo estiver incorreto, reporte aqui. O conferente será notificado.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="acao" value="reportar">
                        <div class="form-row">
                            <div>
                                <label for="tipo_erro">Tipo de Erro *</label>
                                <select id="tipo_erro" name="tipo_erro" required>
                                    <option value="">-- Selecione --</option>
                                    <option value="Peso errado">Peso errado</option>
                                    <option value="Item faltando">Item faltando</option>
                                    <option value="Item sobrando">Item sobrando</option>
                                    <option value="Avarias não registradas">Avarias não registradas</option>
                                    <option value="Dados incorretos">Dados incorretos</option>
                                    <option value="Outro">Outro</option>
                                </select>
                            </div>
                        </div>
                        <label for="motivo">Descreva o problema *</label>
                        <input type="text" id="motivo" name="motivo" placeholder="Ex: Faltou 1 saca de soja" maxlength="300" required>
                        <button type="submit" style="background:#dc3545;">⚠️ Reportar Erro</button>
                    </form>
                </div>

            <?php elseif (empty($operacao['justificado_pelo_conferente'])): ?>
                <div class="alerta-notificacao" style="margin-top:30px;">
                    <strong>⚠️ Aguardando resposta do conferente</strong>
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($operacao['tipo_erro']); ?></p>
                    <p><strong>Motivo:</strong> <?php echo htmlspecialchars($operacao['motivo_reporte']); ?></p>
                    <p style="margin-top:10px; font-size:14px;">O conferente foi notificado e pode reabrir a operação ou justificar.</p>
                </div>

            <?php else: ?>
                <div class="alerta-notificacao" style="margin-top:30px; background:#f0f4f8; border-left-color:#0b2b40;">
                    <strong style="color:#0b2b40;">💬 O conferente justificou. Revise a resposta:</strong>
                    <p style="color:#0b2b40;"><em>"<?php echo htmlspecialchars($operacao['justificativa_conferente']); ?>"</em></p>
                    <p style="margin-top:10px; font-size:13px; color:#555;">
                        Se você não concorda com a justificativa, pode reportar novamente com mais evidências.
                    </p>

                    <form method="POST" action="" style="margin-top:15px; background:#fff; padding:15px; border-radius:8px;">
                        <input type="hidden" name="acao" value="reportar_novamente">
                        <strong style="color:#dc3545;">🔁 Reportar Novamente</strong>
                        <p style="font-size:13px; color:#666; margin:5px 0;">Altere o motivo se necessário:</p>
                        
                        <div class="form-row">
                            <div>
                                <label for="tipo_erro_novo">Tipo de Erro *</label>
                                <select id="tipo_erro_novo" name="tipo_erro" required>
                                    <option value="<?php echo htmlspecialchars($operacao['tipo_erro']); ?>">
                                        <?php echo htmlspecialchars($operacao['tipo_erro']); ?> (atual)
                                    </option>
                                    <option value="Peso errado">Peso errado</option>
                                    <option value="Item faltando">Item faltando</option>
                                    <option value="Item sobrando">Item sobrando</option>
                                    <option value="Avarias não registradas">Avarias não registradas</option>
                                    <option value="Dados incorretos">Dados incorretos</option>
                                    <option value="Outro">Outro</option>
                                </select>
                            </div>
                        </div>
                        <label for="motivo_novo">Motivo *</label>
                        <input type="text" id="motivo_novo" name="motivo" value="<?php echo htmlspecialchars($operacao['motivo_reporte']); ?>" maxlength="300" required>
                        <button type="submit" style="background:#dc3545;">🔁 Reportar Novamente</button>
                    </form>
                </div>
            <?php endif; ?>

            <div style="margin-top:30px; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="gerar_packing_list.php?id=<?php echo $operacao['id']; ?>" class="btn-secondary" style="background:#28a745;">📄 Gerar Packing List</a>
                <a href="dashboard_adm.php" class="btn-secondary" style="background:#6c757d;">← Voltar</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/validacao-tempo-real.js"></script>
    <script src="../assets/js/navegacao.js"></script>
    <script src="../assets/js/ux.js"></script>
</body>
</html>