<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/OperacaoJsonRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'adm') {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard_adm.php');
    exit;
}

$repoOp = new OperacaoJsonRepository();

if (isset($_GET['acao']) && $_GET['acao'] == 'desarquivar') {
    $repoOp->desarquivarOperacao($id, $_SESSION['usuario']);
} else {
    $repoOp->arquivarOperacao($id, $_SESSION['usuario']);
}

header('Location: dashboard_adm.php');
exit;
?>