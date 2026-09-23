<?php
session_start();
include_once '../inc/config.php';
include_once '../repositories/OperacaoJsonRepository.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'conferente') {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard_conferente_v2.php');
    exit;
}

$repoOp = new OperacaoJsonRepository();
$repoOp->pegarOperacao($id, $_SESSION['usuario']);

header('Location: detalhes_operacao.php?id=' . $id);
exit;
?>