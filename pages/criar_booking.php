<?php
session_start();
include_once '../inc/config.php';
include_once '../inc/validacoes.php';
include_once '../repositories/OperacaoJsonRepository.php';
include_once '../repositories/ClienteRepository.php';
include_once '../repositories/ProdutoRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'supervisor') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repoOp = new OperacaoJsonRepository();
$repoCliente = new ClienteRepository();
$repoProduto = new ProdutoRepository();
$clientes = $repoCliente->listarAtivos();
$produtos_ativos = $repoProduto->listarAtivos();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $numero_booking = trim($_POST['numero_booking'] ?? '');
    $cliente_id = (int)($_POST['cliente_id'] ?? 0);
    $tipo_mercadoria = trim($_POST['tipo_mercadoria'] ?? '');
    $quantidade = (int)($_POST['quantidade_estimada'] ?? 0);
    $peso = str_replace(',', '.', $_POST['peso_estimado'] ?? 0);
    $data_prevista = trim($_POST['data_prevista'] ?? '');
    $obs = trim($_POST['observacoes_booking'] ?? '');

    $cliente = $repoCliente->buscarPorId($cliente_id);
    $cliente_nome = $cliente ? $cliente['nome'] : '';

    $erros = [];
    if (!validarBooking($numero_booking)) $erros[] = 'Número do Booking inválido (3 a 30 caracteres, letras/números/hífen).';
    if ($cliente_id <= 0) $erros[] = 'Selecione um cliente.';
    if (empty($tipo_mercadoria) || !validarTexto($tipo_mercadoria, 100)) $erros[] = 'Tipo de mercadoria inválido.';
    if (!validarQuantidade($quantidade)) $erros[] = 'Quantidade estimada deve ser um número entre 1 e 999.999.';
    if (!empty($peso) && !validarPeso($peso)) $erros[] = 'Peso estimado inválido (máx. 2 casas decimais).';
    if (!empty($data_prevista) && !validarDataFormato($data_prevista)) $erros[] = 'Data prevista inválida (use dd/mm/aaaa).';
    if (!empty($obs) && strlen($obs) > 200) $erros[] = 'Observações muito longas (máx. 200 caracteres).';

    if (count($erros) > 0) {
        $erro = implode('<br>', $erros);
    } else {
        $repoOp->criarBooking([
            'numero_booking' => $numero_booking,
            'cliente_id' => $cliente_id,
            'cliente_nome' => $cliente_nome,
            'tipo_mercadoria' => $tipo_mercadoria,
            'quantidade_estimada' => $quantidade,
            'peso_estimado' => (float)$peso,
            'data_prevista' => $data_prevista,
            'observacoes_booking' => $obs
        ], $usuario);
        $mensagem = "✅ Booking <strong>" . htmlspecialchars($numero_booking) . "</strong> criado com sucesso!";
    }
}

$bookings = $repoOp->listarPorStatus('Aberta');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Novo Booking</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Novo Booking</h2>
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


            <div class="form-container">
                <h3>📋 Criar Novo Booking (Semi-Operação)</h3>
                <p style="color:#666; font-size:14px;">Informe o básico que o cliente passou. O conferente completará com os detalhes.</p>

                <?php if ($mensagem): ?><div class="mensagem"><?php echo $mensagem; ?></div><?php endif; ?>
                <?php if ($erro): ?><div class="erro"><?php echo $erro; ?></div><?php endif; ?>

                <form method="POST" action="">
                    <div class="form-row">
                        <div>
                            <label for="numero_booking">Número do Booking *</label>
                            <input type="text" id="numero_booking" name="numero_booking" placeholder="Ex: BKG-2024-001" required maxlength="30">
                        </div>
                        <div>
                            <label for="cliente_id">Cliente *</label>
                            <select id="cliente_id" name="cliente_id" required>
                                <option value="">-- Selecione o cliente --</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="tipo_mercadoria">Tipo de Mercadoria *</label>
                            <input type="text" id="tipo_mercadoria" name="tipo_mercadoria" list="lista-mercadorias" placeholder="Digite para buscar..." required maxlength="100" autocomplete="off">
                            <datalist id="lista-mercadorias">
                                <?php foreach ($produtos_ativos as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p['nome']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div>
                            <label for="quantidade_estimada">Quantidade Estimada *</label>
                            <input type="number" id="quantidade_estimada" name="quantidade_estimada" placeholder="Ex: 540" min="1" required>
                        </div>
                        <div>
                            <label for="peso_estimado">Peso Estimado (kg)</label>
                            <input type="text" id="peso_estimado" name="peso_estimado" placeholder="Ex: 27000" pattern="[0-9]+([,.][0-9]+)?">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="data_prevista">Data Prevista</label>
                            <input type="text" id="data_prevista" name="data_prevista" placeholder="Ex: 15/09/2026" maxlength="10">
                        </div>
                        <div>
                            <label for="observacoes_booking">Observações do Booking</label>
                            <input type="text" id="observacoes_booking" name="observacoes_booking" placeholder="Ex: Carga prioritária" maxlength="200">
                        </div>
                    </div>

                    <button type="submit">📌 Criar Booking</button>
                </form>
            </div>

            <div class="table-container">
                <h3 style="color:#0b2b40; margin-top:0;">📋 Bookings Abertos (aguardando conferente)</h3>
                <?php if (count($bookings) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Booking</th>
                                <th>Cliente</th>
                                <th>Mercadoria</th>
                                <th>Qtd. Estimada</th>
                                <th>Peso Est.</th>
                                <th>Criado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td>#<?php echo $b['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($b['numero_booking']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($b['cliente_nome'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($b['tipo_mercadoria']); ?></td>
                                    <td><?php echo number_format($b['quantidade_estimada'], 0, ',', '.'); ?></td>
                                    <td><?php echo number_format($b['peso_estimado'], 2, ',', '.'); ?> kg</td>
                                    <td><?php echo $b['data_criacao']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:#999;">Nenhum Booking aberto no momento.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Máscara de data automática (dd/mm/aaaa)
        const inputData = document.getElementById('data_prevista');
        if (inputData) {
            inputData.addEventListener('input', function(e) {
                let v = e.target.value.replace(/\D/g, '').substring(0, 8);
                if (v.length >= 5) {
                    v = v.replace(/^(\d{2})(\d{2})(\d)/, '$1/$2/$3');
                } else if (v.length >= 3) {
                    v = v.replace(/^(\d{2})(\d)/, '$1/$2');
                }
                e.target.value = v;
            });
        }
    </script>

<script src="../assets/js/validacao-tempo-real.js"></script>
  <script src="../assets/js/navegacao.js"></script>  
  <script src="../assets/js/ux.js"></script>
</body>
</html>