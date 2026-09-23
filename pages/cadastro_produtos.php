<?php
include_once '../inc/config.php';
include_once '../inc/validacoes.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $categoria = $_POST['categoria'] ?? '';
    $lote = trim($_POST['lote'] ?? '');
    $quantidade = (int)($_POST['quantidade'] ?? 0);

    $erros = [];
    if (!validarTexto($nome, 100)) {
        $erros[] = 'Nome inválido (máx. 100 caracteres).';
    }
    if (!in_array($categoria, ['Grãos', 'Madeira', 'Eletrônicos', 'Alimentos'])) {
        $erros[] = 'Categoria inválida.';
    }
    if (!validarLote($lote)) {
        $erros[] = 'Lote inválido (use letras, números, hífen ou underline).';
    }
    if (!validarQuantidade($quantidade)) {
        $erros[] = 'Quantidade deve ser um número inteiro positivo.';
    }

    if (count($erros) > 0) {
        $mensagem = '❌ ' . implode('<br>', $erros);
    } else {
        // Simula salvamento (como antes)
        $mensagem = '✅ Produto "' . htmlspecialchars($nome) . '" cadastrado com sucesso! (Dados salvos no MOCK)';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>LogiStock - Cadastrar Produto</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: block; background-color: #f4f7fa; }
        .form-container {
            background: white; padding: 30px; border-radius: 12px;
            max-width: 600px; margin: 120px auto 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .form-container label { font-weight: 600; display: block; margin-top: 15px; }
        .form-container input, .form-container select {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; margin-top: 5px;
        }
        .form-container button {
            margin-top: 25px; padding: 12px; background: #0b2b40; color: white;
            border: none; border-radius: 6px; font-weight: bold; width: 100%; cursor: pointer;
        }
        .mensagem { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .voltar { display: inline-block; margin-top: 15px; color: #0b2b40; text-decoration: none; font-weight: bold; }
        .erro { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <h2>🚢 LogiStock</h2>
        <div><a href="dashboard_admin.php" style="color:white;">⬅ Voltar ao Painel</a></div>
    </header>

    <div class="form-container">
        <h3 style="margin-top:0;">➕ Cadastrar Novo Produto</h3>
        <?php if ($mensagem): ?>
            <div class="<?php echo (strpos($mensagem, '❌') !== false) ? 'erro' : 'mensagem'; ?>"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="nome">Nome do Produto / Carga</label>
            <input type="text" id="nome" name="nome" placeholder="Ex: Soja, Madeira, Placa Mãe" required maxlength="100">

            <label for="categoria">Categoria</label>
            <select id="categoria" name="categoria" required>
                <option value="Grãos">Grãos</option>
                <option value="Madeira">Madeira</option>
                <option value="Eletrônicos">Eletrônicos</option>
                <option value="Alimentos">Alimentos</option>
            </select>

            <label for="lote">Lote / Referência</label>
            <input type="text" id="lote" name="lote" placeholder="Ex: LOT-2024-F7" required pattern="[a-zA-Z0-9\-_]+" maxlength="30">

            <label for="quantidade">Quantidade Esperada</label>
            <input type="number" id="quantidade" name="quantidade" placeholder="Ex: 200" required min="1" step="1">

            <button type="submit">📥 Cadastrar Produto</button>
        </form>
        <a href="dashboard_admin.php" class="voltar">← Voltar ao Dashboard</a>
    </div>
</body>
</html>