<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/OperacaoJsonRepository.php';

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['perfil'], ['supervisor', 'adm'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard_supervisor.php');
    exit;
}

$repoOp = new OperacaoJsonRepository();
$repoOp->excluirOperacao($id, $_SESSION['usuario']);

$voltar = ($_SESSION['usuario']['perfil'] == 'adm') ? 'dashboard_adm.php' : 'dashboard_supervisor.php';
header('Location: ' . $voltar);
exit;
?>