<?php
/**
 * Repositório de Produtos (Mercadorias) - persistência em JSON.
 */
class ProdutoRepository {

    private $arquivo;

    public function __construct() {
        $this->arquivo = __DIR__ . '/../data/produtos.json';
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
        foreach ($this->ler() as $p) {
            if (!empty($p['ativo'])) $resultado[] = $p;
        }
        return $resultado;
    }

    public function buscarPorId($id) {
        foreach ($this->ler() as $p) {
            if ($p['id'] == $id) return $p;
        }
        return null;
    }

    public function buscarPorNome($nome) {
        $nome = mb_strtolower(trim($nome));
        foreach ($this->ler() as $p) {
            if (mb_strtolower($p['nome']) === $nome) return $p;
        }
        return null;
    }

    /**
     * Busca produtos cujo nome contenha o termo (para autocomplete).
     */
    public function buscarPorTermo($termo) {
        $termo = mb_strtolower(trim($termo));
        $resultado = [];
        foreach ($this->ler() as $p) {
            if (empty($p['ativo'])) continue;
            if (mb_strpos(mb_strtolower($p['nome']), $termo) !== false) {
                $resultado[] = $p;
            }
        }
        return $resultado;
    }

    public function criar($dados) {
        $produtos = $this->ler();
        $ids = array_column($produtos, 'id');
        $id = (count($ids) > 0) ? max($ids) + 1 : 1;

        $produto = [
            'id' => $id,
            'nome' => trim($dados['nome'] ?? ''),
            'categoria' => trim($dados['categoria'] ?? 'Outros'),
            'unidade_padrao' => trim($dados['unidade_padrao'] ?? ''),
            'peso_unitario_padrao' => (float)($dados['peso_unitario_padrao'] ?? 0),
            'ativo' => true
        ];

        $produtos[] = $produto;
        $this->escrever($produtos);
        return $produto;
    }

    public function atualizar($id, $dados) {
        $produtos = $this->ler();
        foreach ($produtos as &$p) {
            if ($p['id'] == $id) {
                if (isset($dados['nome'])) $p['nome'] = trim($dados['nome']);
                if (isset($dados['categoria'])) $p['categoria'] = trim($dados['categoria']);
                if (isset($dados['unidade_padrao'])) $p['unidade_padrao'] = trim($dados['unidade_padrao']);
                if (isset($dados['peso_unitario_padrao'])) $p['peso_unitario_padrao'] = (float)$dados['peso_unitario_padrao'];
                if (isset($dados['ativo'])) $p['ativo'] = (bool)$dados['ativo'];
                $this->escrever($produtos);
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
     * Retorna a lista única de categorias existentes.
     */
    public function listarCategorias() {
        $categorias = [];
        foreach ($this->ler() as $p) {
            if (!empty($p['categoria']) && !in_array($p['categoria'], $categorias)) {
                $categorias[] = $p['categoria'];
            }
        }
        sort($categorias);
        return $categorias;
    }
}
?>