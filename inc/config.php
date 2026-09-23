<?php
// Esse arquivo controla se o sistema vai usar os dados FALSOS (trabalho) 
// ou o Banco de Dados real (casa).
// Quando estiver no trabalho, deixe 'mock'. Quando for usar MySQL, troque para 'db'.

define('AMBIENTE', 'mock'); 

// Definição da URL base (ajuste se colocar dentro de subpastas no servidor)
define('BASE_URL', '/LogiStock/');

// Função de debug pra ajudar a ver os dados (opcional)
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    // die(); // Descomente se quiser parar a execução
}
?>