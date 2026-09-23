<?php
session_start();
include_once '../inc/config.php';

include_once '../inc/validacoes.php';
include_once '../repositories/OperacaoJsonRepository.php';
include_once '../repositories/CargaMockRepository.php';

// Verifica se o usuário é conferente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'conferente') {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repoOp = new OperacaoJsonRepository();
$repoCarga = new CargaMockRepository();
$produtos = $repoCarga->listarTodas();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $erros = [];

    // Valida container
    $container = $_POST['container'] ?? '';
    if (empty($container) || !validarContainer($container)) {
        $erros[] = 'Container inválido (use letras, números e hífen).';
    }

    // Valida local (opcional, mas se preenchido, deve ser texto)
    $local = $_POST['local'] ?? '';
    if (!empty($local) && !validarTexto($local, 50)) {
        $erros[] = 'Local inválido (máx. 50 caracteres).';
    }

    // Valida lacre
    $lacre = $_POST['lacre'] ?? '';
    if (!empty($lacre) && !validarTexto($lacre, 30)) {
        $erros[] = 'Lacre inválido (máx. 30 caracteres).';
    }

    // Valida navio
    $navio = $_POST['navio'] ?? '';
    if (!empty($navio) && !validarTexto($navio, 50)) {
        $erros[] = 'Navio inválido (máx. 50 caracteres).';
    }

    // Valida itens
    $itens = [];
    if (isset($_POST['descricao']) && is_array($_POST['descricao'])) {
        for ($i = 0; $i < count($_POST['descricao']); $i++) {
            $desc = trim($_POST['descricao'][$i] ?? '');
            $qtd = $_POST['quantidade'][$i] ?? 0;
            $unidade = trim($_POST['unidade'][$i] ?? '');
            $peso = str_replace(',', '.', $_POST['peso_unitario'][$i] ?? 0);
            $lote_item = trim($_POST['lote'][$i] ?? '');
            $nf = trim($_POST['nota_fiscal'][$i] ?? '');

            // Valida descrição
            if (empty($desc) || !validarTexto($desc, 100)) {
                $erros[] = "Item " . ($i+1) . ": descrição inválida (máx. 100 caracteres).";
            }
            // Valida quantidade
            if (!validarQuantidade($qtd)) {
                $erros[] = "Item " . ($i+1) . ": quantidade deve ser um número inteiro positivo.";
            }
            // Valida peso unitário (opcional, mas se preenchido)
            if (!empty($peso) && !validarPeso($peso)) {
                $erros[] = "Item " . ($i+1) . ": peso unitário deve ser um número (ex: 50 ou 50,5).";
            }
            // Valida lote (opcional)
            if (!empty($lote_item) && !validarLote($lote_item)) {
                $erros[] = "Item " . ($i+1) . ": lote inválido (use letras, números, hífen ou underline).";
            }
            // Valida nota fiscal (opcional)
            if (!empty($nf) && !validarNotaFiscal($nf)) {
                $erros[] = "Item " . ($i+1) . ": nota fiscal inválida (ex: 38638-340).";
            }
            // Se não houve erro para este item, adiciona
            if (empty($erros) || count($erros) == 0) {
                $itens[] = [
                    'descricao' => $desc,
                    'quantidade' => (int)$qtd,
                    'unidade' => $unidade,
                    'peso_unitario' => (float)$peso,
                    'lote' => $lote_item,
                    'nota_fiscal' => $nf
                ];
            }
        }
    }

    if (empty($container) || count($itens) == 0) {
        $erros[] = 'Preencha o container e adicione pelo menos um item válido.';
    }

    if (count($erros) > 0) {
        $erro = implode('<br>', $erros);
    } else {
        $dados = [
            'container' => $container,
            'conferente_id' => $usuario['id'],
            'conferente_nome' => $usuario['nome'],
            'local' => $local,
            'lacre' => $lacre,
            'navio' => $navio,
            'tipo_operacao' => $_POST['tipo_operacao'] ?? 'carregamento',
            'itens' => $itens
        ];
        $nova = $repoOp->criar($dados);
        $mensagem = "✅ Operação <strong>{$nova['container']}</strong> criada com sucesso! ID: #{$nova['id']}<br>Agora você pode conferir os itens.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Nova Operação</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: block; background-color: #f4f7fa; }
        .form-container {
            background: white; padding: 30px; border-radius: 12px;
            max-width: 900px; margin: 120px auto 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .form-container label { font-weight: 600; display: block; margin-top: 12px; }
        .form-container input, .form-container select {
            width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; margin-top: 3px;
        }
        .form-container .row { display: flex; gap: 15px; flex-wrap: wrap; }
        .form-container .row > div { flex: 1; min-width: 150px; }
        .form-container button {
            margin-top: 20px; padding: 12px; background: #0b2b40; color: white;
            border: none; border-radius: 6px; font-weight: bold; width: 100%; cursor: pointer;
        }
        .form-container button:hover { background: #1a4b66; }
        .mensagem { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .erro { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .voltar { display: inline-block; margin-top: 15px; color: #0b2b40; text-decoration: none; font-weight: bold; }
        .item-linha {
            background: #f9f9f9; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #0b2b40;
        }
        .btn-remover-item { background: #dc3545; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer; margin-top: 10px; }
        .btn-adicionar-item { background: #28a745; color: white; border: none; border-radius: 4px; padding: 8px 16px; cursor: pointer; margin-top: 15px; }
        .sugestao-produto { font-size: 12px; color: #666; cursor: pointer; }
        .sugestao-produto:hover { color: #0b2b40; text-decoration: underline; }
        .btn-finalizar-criacao {
            background: #28a745; color: white; padding: 12px; border: none; border-radius: 6px;
            font-weight: bold; width: 100%; cursor: pointer; margin-top: 10px;
        }
        .btn-finalizar-criacao:hover { background: #1e7e34; }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock - Nova Operação</h2>
        <div><a href="dashboard_conferente_v2.php" style="color:white;">⬅ Voltar</a></div>
    </header>

    <div class="form-container">
        <h3 style="margin-top:0;">📦 Criar Nova Operação</h3>
        <p style="color:#666; font-size:14px;">Preencha todos os dados do container e adicione os itens da carga.</p>
        
        <?php if ($mensagem): ?>
            <div class="mensagem"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="erro"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="formOperacao">
            <h4 style="margin-top:20px; color:#0b2b40;">Dados do Container</h4>
            <div class="row">
                <div>
                    <label for="container">Nº Container *</label>
                    <input type="text" id="container" name="container" placeholder="Ex: TCNU176382-5" required pattern="[A-Z0-9\-]+" maxlength="20" title="Use letras maiúsculas, números e hífen.">
                </div>
                <div>
                    <label for="local">Local / Armazém</label>
                    <input type="text" id="local" name="local" placeholder="Ex: Sulterminais" pattern="[a-zA-ZÀ-ÿ0-9\s\-_]+" maxlength="50">
                </div>
                <div>
                    <label for="lacre">Lacre</label>
                    <input type="text" id="lacre" name="lacre" placeholder="Ex: HUK035870" pattern="[a-zA-Z0-9\-_]+" maxlength="30">
                </div>
            </div>
            <div class="row">
                <div>
                    <label for="navio">Navio</label>
                    <input type="text" id="navio" name="navio" placeholder="Ex: MSC MICHELIN" pattern="[a-zA-ZÀ-ÿ0-9\s\-_]+" maxlength="50">
                </div>
                <div>
                    <label for="tipo_operacao">Tipo de Operação</label>
                    <select id="tipo_operacao" name="tipo_operacao">
                        <option value="carregamento">Carregamento</option>
                        <option value="descarregamento">Descarregamento</option>
                    </select>
                </div>
            </div>

            <h4 style="margin-top:30px; color:#0b2b40;">Itens da Carga</h4>
            <p style="font-size:13px; color:#666;">Clique em "Adicionar Item" para incluir produtos.</p>

            <div id="lista-itens"></div>

            <button type="button" class="btn-adicionar-item" onclick="adicionarItem()">➕ Adicionar Item</button>
            <button type="submit" class="btn-finalizar-criacao">📌 Criar Operação e Iniciar Conferência</button>
        </form>

        <a href="dashboard_conferente_v2.php" class="voltar">← Voltar ao Dashboard</a>
    </div>

    <script>
        const produtos = <?php echo json_encode($produtos); ?>;
        let contadorItens = 0;

        function adicionarItem(produto = null) {
            const container = document.getElementById('lista-itens');
            const div = document.createElement('div');
            div.className = 'item-linha';
            div.id = 'item-' + contadorItens;

            let descricao = '', lote = '';
            if (produto) {
                descricao = produto.nome;
                lote = produto.lote;
            }

            div.innerHTML = `
                <div class="row">
                    <div>
                        <label>Descrição *</label>
                        <input type="text" name="descricao[]" value="${descricao}" placeholder="Ex: Soja em Grãos" required maxlength="100">
                        <div class="sugestao-produto" onclick="sugerirProduto(this)">🔍 Sugerir produto</div>
                    </div>
                    <div>
                        <label>Quantidade</label>
                        <input type="number" name="quantidade[]" value="1" step="1" min="1" required>
                    </div>
                    <div>
                        <label>Unidade</label>
                        <input type="text" name="unidade[]" placeholder="Ex: sacas 50kg" maxlength="30">
                    </div>
                    <div>
                        <label>Peso Unitário (kg)</label>
                        <input type="text" name="peso_unitario[]" placeholder="Ex: 50" step="0.01" min="0" pattern="[0-9]+([,.][0-9]+)?">
                    </div>
                    <div>
                        <label>Lote</label>
                        <input type="text" name="lote[]" value="${lote}" placeholder="Ex: LOT-2024-A1" pattern="[a-zA-Z0-9\-_]+" maxlength="30">
                    </div>
                    <div>
                        <label>Nota Fiscal</label>
                        <input type="text" name="nota_fiscal[]" placeholder="Ex: 38638-340" pattern="[0-9\-]+" maxlength="20">
                    </div>
                </div>
                <button type="button" class="btn-remover-item" onclick="removerItem(${contadorItens})">🗑️ Remover Item</button>
            `;

            container.appendChild(div);
            contadorItens++;
        }

        function removerItem(id) {
            const el = document.getElementById('item-' + id);
            if (el) el.remove();
        }

        function sugerirProduto(elemento) {
            const item = elemento.closest('.item-linha');
            const inputDesc = item.querySelector('input[name="descricao[]"]');
            const inputLote = item.querySelector('input[name="lote[]"]');

            let lista = produtos.map((p, i) => `${i+1}. ${p.nome} (Lote: ${p.lote})`).join('\n');
            let escolha = prompt('Digite o número do produto desejado:\n' + lista);
            if (escolha) {
                const idx = parseInt(escolha) - 1;
                if (idx >= 0 && idx < produtos.length) {
                    inputDesc.value = produtos[idx].nome;
                    inputLote.value = produtos[idx].lote;
                } else {
                    alert('Número inválido!');
                }
            }
        }

        // Validação extra antes de enviar
        document.getElementById('formOperacao').addEventListener('submit', function(e) {
            const itens = document.querySelectorAll('.item-linha');
            if (itens.length === 0) {
                alert('Adicione pelo menos um item!');
                e.preventDefault();
                return false;
            }
            return true;
        });

        window.onload = function() { adicionarItem(); };
    </script>
</body>
</html>