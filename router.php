<?php
/**
 * Router do servidor embutido do PHP.
 * 
 * Intercepta todas as requisições e:
 * 1. Deixa passar arquivos existentes (css, js, imagens, php).
 * 2. Bloqueia acesso direto a JSON e pastas sensíveis.
 * 3. Redireciona URLs inexistentes para o 404.php.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$uri = str_replace('/LogiStock', '', $uri); // Remove o prefixo se houver
$arquivo = __DIR__ . $uri;

// 1. Bloqueia acesso a arquivos .json
if (preg_match('/\.json$/i', $uri)) {
    http_response_code(403);
    echo '<h1>403 - Acesso negado</h1><p>Você não tem permissão para acessar este arquivo.</p>';
    exit;
}

// 2. Bloqueia listagem de diretórios
if (is_dir($arquivo)) {
    // Se tiver index.php dentro, redireciona
    if (file_exists($arquivo . '/index.php')) {
        require $arquivo . '/index.php';
        return true;
    }
    http_response_code(403);
    echo '<h1>403 - Acesso negado</h1><p>Listagem de diretórios não permitida.</p>';
    exit;
}

// 3. Se o arquivo existe (css, js, imagens, php), deixa passar
if (file_exists($arquivo)) {
    return false; // false = deixa o PHP embutido servir o arquivo normalmente
}

// 4. Se chegou aqui, é 404
http_response_code(404);
require __DIR__ . '/pages/404.php';
return true;
?>