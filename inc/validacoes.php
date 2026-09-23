<?php
/**
 * Funções de validação para os formulários do LogiStock
 */

function validarTexto($texto, $max = 100, $min = 1) {
    $texto = trim($texto);
    if (strlen($texto) < $min || strlen($texto) > $max) {
        return false;
    }
    return preg_match('/^[a-zA-ZÀ-ÿ0-9\s\-_\.;]+$/', $texto) === 1;
}

function validarNomeCliente($nome) {
    $nome = trim($nome);
    if (strlen($nome) < 3 || strlen($nome) > 100) return false;
    return preg_match('/^[a-zA-ZÀ-ÿ0-9\s\-_]+$/', $nome) === 1;
}

function validarBooking($booking) {
    $booking = trim($booking);
    if (strlen($booking) < 3 || strlen($booking) > 30) return false;
    return preg_match('/^[A-Za-z0-9\-]+$/', $booking) === 1;
}

// Valida número de container no padrão ISO 6346: 4 letras maiúsculas + 7 números
function validarContainer($container) {
    $container = strtoupper(trim($container));
    $container_limpo = str_replace('-', '', $container);
    return preg_match('/^[A-Z]{4}[0-9]{7}$/', $container_limpo) === 1;
}

function validarLacre($lacre) {
    $lacre = strtoupper(trim($lacre));
    if (strlen($lacre) > 30) return false;
    if (empty($lacre)) return true;
    return preg_match('/^[A-Z0-9\-]+$/', $lacre) === 1;
}

function validarQuantidade($qtd) {
    $qtd = (int)$qtd;
    return $qtd >= 1 && $qtd <= 999999;
}

function validarPeso($peso) {
    $peso = str_replace(',', '.', (string)$peso);
    if (!is_numeric($peso)) return false;
    $peso = (float)$peso;
    if ($peso < 0 || $peso > 999999999) return false;
    if (preg_match('/\.\d{3,}$/', (string)$peso)) return false;
    return true;
}

function validarLote($lote) {
    if (strlen($lote) > 30) return false;
    return preg_match('/^[a-zA-Z0-9\-_]+$/', $lote) === 1;
}

function validarNotaFiscal($nf) {
    if (strlen($nf) > 20) return false;
    return preg_match('/^[0-9\-]+$/', $nf) === 1;
}

function validarData($data) {
    $partes = explode('/', $data);
    if (count($partes) != 3) return false;
    return checkdate((int)$partes[1], (int)$partes[0], (int)$partes[2]);
}

function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) != 14) return false;
    if (preg_match('/^(\d)\1+$/', $cnpj)) return false;

    $soma = 0;
    $pesos = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0; $i < 12; $i++) $soma += (int)$cnpj[$i] * $pesos[$i];
    $resto = $soma % 11;
    $dig1 = ($resto < 2) ? 0 : 11 - $resto;
    if ((int)$cnpj[12] !== $dig1) return false;

    $soma = 0;
    $pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0; $i < 13; $i++) $soma += (int)$cnpj[$i] * $pesos[$i];
    $resto = $soma % 11;
    $dig2 = ($resto < 2) ? 0 : 11 - $resto;
    if ((int)$cnpj[13] !== $dig2) return false;

    return true;
}

function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/^(\d)\1+$/', $cpf)) return false;

    $soma = 0;
    for ($i = 0; $i < 9; $i++) $soma += (int)$cpf[$i] * (10 - $i);
    $resto = $soma % 11;
    $dig1 = ($resto < 2) ? 0 : 11 - $resto;
    if ((int)$cpf[9] !== $dig1) return false;

    $soma = 0;
    for ($i = 0; $i < 10; $i++) $soma += (int)$cpf[$i] * (11 - $i);
    $resto = $soma % 11;
    $dig2 = ($resto < 2) ? 0 : 11 - $resto;
    if ((int)$cpf[10] !== $dig2) return false;

    return true;
}

function formatarPeso($peso) {
    return number_format((float)$peso, 2, ',', '.');
}

function validarDataFormato($data) {
    if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) return false;
    $partes = explode('/', $data);
    return checkdate((int)$partes[1], (int)$partes[0], (int)$partes[2]);
}
?>