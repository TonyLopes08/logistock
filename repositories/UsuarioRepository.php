<?php
/**
 * Repositório de usuários - persistência em JSON.
 * Substitui o antigo usuarios_mock.php com dados fixos.
 */
class UsuarioRepository {

    private $arquivo;

    public function __construct() {
        $this->arquivo = __DIR__ . '/../data/usuarios.json';
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

    public function listarTodos() {
        return $this->ler();
    }

    public function buscarPorId($id) {
        foreach ($this->ler() as $u) {
            if ($u['id'] == $id) return $u;
        }
        return null;
    }

    public function buscarPorCpf($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf); // Remove pontuação
        foreach ($this->ler() as $u) {
            if ($u['cpf'] === $cpf) return $u;
        }
        return null;
    }

    public function autenticar($cpf, $senha) {
        $usuario = $this->buscarPorCpf($cpf);
        if ($usuario && $usuario['senha'] === $senha && $usuario['ativo'] === true) {
            return $usuario;
        }
        return null;
    }

    public function criar($dados) {
        $usuarios = $this->ler();
        $ids = array_column($usuarios, 'id');
        $id = (count($ids) > 0) ? max($ids) + 1 : 1;

        $usuario = [
            'id' => $id,
            'nome' => trim($dados['nome'] ?? ''),
            'cpf' => preg_replace('/[^0-9]/', '', $dados['cpf'] ?? ''),
            'email' => trim($dados['email'] ?? ''),
            'senha' => $dados['senha'] ?? '123',
            'perfil' => $dados['perfil'] ?? 'conferente',
            'ativo' => true
        ];

        $usuarios[] = $usuario;
        $this->escrever($usuarios);
        return $usuario;
    }

    public function atualizar($id, $dados) {
        $usuarios = $this->ler();
        foreach ($usuarios as &$u) {
            if ($u['id'] == $id) {
                if (isset($dados['nome'])) $u['nome'] = trim($dados['nome']);
                if (isset($dados['email'])) $u['email'] = trim($dados['email']);
                if (isset($dados['senha']) && !empty($dados['senha'])) $u['senha'] = $dados['senha'];
                if (isset($dados['perfil'])) $u['perfil'] = $dados['perfil'];
                if (isset($dados['ativo'])) $u['ativo'] = (bool)$dados['ativo'];
                $this->escrever($usuarios);
                return true;
            }
        }
        return false;
    }

    public function listarConferentes() {
        $resultado = [];
        foreach ($this->ler() as $u) {
            if ($u['perfil'] == 'conferente' && $u['ativo']) {
                $resultado[] = $u;
            }
        }
        return $resultado;
    }

    // Formata CPF para exibição
    public static function formatarCpf($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11) return $cpf;
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
}
?>