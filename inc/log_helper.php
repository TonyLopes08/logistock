<?php
/**
 * Funções auxiliares para exibição do log de auditoria.
 */

function formatarLog($log) {
    if (!is_array($log) || count($log) == 0) {
        return '<p style="color:#999;">Nenhuma ação registrada.</p>';
    }
    
    $html = '<ul style="list-style:none; padding:0; margin:0;">';
    // Exibe do mais recente para o mais antigo
    $log_reverso = array_reverse($log);
    foreach ($log_reverso as $entrada) {
        $html .= '<li style="padding:8px 0; border-bottom:1px solid #eee; font-size:13px;">';
        $html .= '<strong style="color:#0b2b40;">' . htmlspecialchars($entrada['usuario_nome']) . '</strong> ';
        $html .= '<span style="color:#555;">' . htmlspecialchars($entrada['acao']) . '</span>';
        $html .= '<br><small style="color:#999;">' . $entrada['data'] . '</small>';
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}
?>