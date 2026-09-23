<?php
/**
 * Repositório de E-mails Pendentes (simulação).
 */
class EmailRepository {

    private $arquivo;

    public function __construct() {
        $this->arquivo = __DIR__ . '/../data/emails_pendentes.json';
        if (!is_dir(dirname($this->arquivo))) {
            mkdir(dirname($this->arquivo), 0777, true);
        }
        if (!file_exists($this->arquivo)) {
            file_put_contents($this->arquivo, json_encode([]));
        }
    }

    private function ler() {
        $conteudo = file_get_contents($this->arquivo);
        return json_decode($conteudo, true) ?: [];
    }

    private function escrever($dados) {
        file_put_contents($this->arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function registrar($para_email, $para_nome, $assunto, $corpo, $tipo = 'geral', $operacao_id = null) {
        $emails = $this->ler();
        $id = count($emails) > 0 ? max(array_column($emails, 'id')) + 1 : 1;

        $emails[] = [
            'id' => $id,
            'para_email' => $para_email,
            'para_nome' => $para_nome,
            'assunto' => $assunto,
            'corpo' => $corpo,
            'tipo' => $tipo,
            'operacao_id' => $operacao_id,
            'data_registro' => date('d/m/Y H:i:s'),
            'enviado' => false,
            'lido' => false
        ];

        $this->escrever($emails);
        return $id;
    }

    public function listarTodos() {
        return $this->ler();
    }

    /**
     * Lista e-mails de um destinatário específico (por e-mail).
     */
    public function listarPorUsuario($email) {
        $resultado = [];
        foreach ($this->ler() as $e) {
            if (($e['para_email'] ?? '') === $email) {
                $resultado[] = $e;
            }
        }
        return $resultado;
    }

    /**
     * Conta e-mails NÃO LIDOS de um destinatário específico.
     */
    public function contarNaoLidosPorUsuario($email) {
        $total = 0;
        foreach ($this->ler() as $e) {
            if (($e['para_email'] ?? '') === $email && empty($e['lido'])) {
                $total++;
            }
        }
        return $total;
    }

    /**
     * Marca e-mail como lido.
     */
    public function marcarComoLido($id) {
        $emails = $this->ler();
        foreach ($emails as &$e) {
            if ($e['id'] == $id) {
                $e['lido'] = true;
                $this->escrever($emails);
                return true;
            }
        }
        return false;
    }

    /**
     * Marca todos os e-mails de um destinatário como lidos.
     */
    public function marcarTodosComoLidos($email) {
        $emails = $this->ler();
        foreach ($emails as &$e) {
            if (($e['para_email'] ?? '') === $email) {
                $e['lido'] = true;
            }
        }
        $this->escrever($emails);
        return true;
    }

    public function contarPendentes() {
        $total = 0;
        foreach ($this->ler() as $e) {
            if (empty($e['enviado'])) $total++;
        }
        return $total;
    }

    public function marcarComoEnviado($id) {
        $emails = $this->ler();
        foreach ($emails as &$e) {
            if ($e['id'] == $id) {
                $e['enviado'] = true;
                $e['data_envio'] = date('d/m/Y H:i:s');
                $this->escrever($emails);
                return true;
            }
        }
        return false;
    }
}
?>