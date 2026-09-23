<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/OperacaoJsonRepository.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$repoOp = new OperacaoJsonRepository();
$id = isset($_POST['id_operacao']) ? (int)$_POST['id_operacao'] : 0;
$tem_pendencias = isset($_POST['tem_pendencias']);

if ($id > 0) {
    $op = $repoOp->buscarPorId($id);
    
    // BLOQUEIO: não deixa finalizar sem itens
    if ($op && count($op['itens']) == 0) {
        header('Location: detalhes_operacao.php?id=' . $id);
        exit;
    }
    
    $justificativa = $tem_pendencias ? 'Finalizado com itens pendentes' : null;
    $repoOp->finalizarOperacao($id, $usuario, $justificativa);
}

header('Location: dashboard_conferente_v2.php');
exit;
?>