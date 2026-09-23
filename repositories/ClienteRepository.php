<?php
/**
 * Repositório de Clientes - persistência em JSON.
 */
class ClienteRepository {

    private $arquivo;

    public function __construct() {
        $this->arquivo = __DIR__ . '/../data/clientes.json';
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

    public function listarTodos() {
        return $this->ler();
    }

    public function listarAtivos() {
        $resultado = [];
        foreach ($this->ler() as $c) {
            if (!empty($c['ativo'])) $resultado[] = $c;
        }
        return $resultado;
    }

    public function buscarPorId($id) {
        foreach ($this->ler() as $c) {
            if ($c['id'] == $id) return $c;
        }
        return null;
    }

    public function buscarPorCnpj($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        foreach ($this->ler() as $c) {
            if ($c['cnpj'] === $cnpj) return $c;
        }
        return null;
    }

    public function buscarPorTermo($termo) {
        $termo = mb_strtolower(trim($termo));
        $resultado = [];
        foreach ($this->ler() as $c) {
            if (empty($c['ativo'])) continue;
            if (mb_strpos(mb_strtolower($c['nome']), $termo) !== false) {
                $resultado[] = $c;
            }
        }
        return $resultado;
    }

    public function criar($dados) {
        $clientes = $this->ler();
        $ids = array_column($clientes, 'id');
        $id = (count($ids) > 0) ? max($ids) + 1 : 1;

        $cliente = [
            'id' => $id,
            'nome' => trim($dados['nome'] ?? ''),
            'cnpj' => preg_replace('/[^0-9]/', '', $dados['cnpj'] ?? ''),
            'ativo' => true
        ];

        $clientes[] = $cliente;
        $this->escrever($clientes);
        return $cliente;
    }

    public function atualizar($id, $dados) {
        $clientes = $this->ler();
        foreach ($clientes as &$c) {
            if ($c['id'] == $id) {
                if (isset($dados['nome'])) $c['nome'] = trim($dados['nome']);
                if (isset($dados['cnpj'])) $c['cnpj'] = preg_replace('/[^0-9]/', '', $dados['cnpj']);
                if (isset($dados['ativo'])) $c['ativo'] = (bool)$dados['ativo'];
                $this->escrever($clientes);
                return true;
            }
        }
        return false;
    }

    public function desativar($id) {
        return $this->atualizar($id, ['ativo' => false]);
    }

    public function reativar($id) {
        return $this->atualizar($id, ['ativo' => true]);
    }

    /**
     * Formata CNPJ para exibição.
     */
    public static function formatarCnpj($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) != 14) return $cnpj;
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }
}
?>