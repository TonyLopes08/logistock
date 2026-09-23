<?php
/**
 * Repositório de Operações (JSON).
 */
class OperacaoJsonRepository {

    private $arquivo;

    public function __construct() {
        $this->arquivo = __DIR__ . '/../data/operacoes.json';
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

    private function gerarId($lista) {
        $ids = array_column($lista, 'id');
        return (count($ids) > 0) ? max($ids) + 1 : 1;
    }

    // ============================================================
    // CONSULTAS
    // ============================================================

    public function listarTodas() {
        return $this->ler();
    }

    public function buscarPorId($id) {
        foreach ($this->ler() as $op) {
            if ($op['id'] == $id) return $op;
        }
        return null;
    }

    public function listarPorStatus($status) {
        $resultado = [];
        foreach ($this->ler() as $op) {
            if ($op['status'] == $status) {
                $resultado[] = $op;
            }
        }
        return $resultado;
    }

    public function listarParaConferente($conferente_id) {
        $resultado = [];
        foreach ($this->ler() as $op) {
            $podeVer = false;

            if ($op['status'] == 'Aberta') {
                $podeVer = true;
            }

            if ($op['status'] == 'Em Andamento') {
                $podeVer = true;
            }

            if ($op['status'] == 'Finalizada') {
                if (!empty($op['reportado_pelo_adm'])
                    && empty($op['justificado_pelo_conferente'])
                    && !empty($op['notificacao_para'])
                    && in_array($conferente_id, $op['notificacao_para'])) {
                    $podeVer = true;
                }
            }

            if ($podeVer) $resultado[] = $op;
        }
        return $resultado;
    }

    public function listarParaAdm($mostrar_arquivadas = false) {
        $resultado = [];
        foreach ($this->ler() as $op) {
            if ($op['status'] == 'Finalizada') {
                $arquivada = !empty($op['arquivada']);
                if (!$mostrar_arquivadas && $arquivada) continue;
                if ($mostrar_arquivadas === 'apenas' && !$arquivada) continue;
                $resultado[] = $op;
            }
        }
        return $resultado;
    }

    public function listarComFiltros($filtros = []) {
        $todas = $this->ler();
        $resultado = [];

        $mostrar_tudo = !empty($filtros['mostrar_tudo']);
        $dias_recentes = (int)($filtros['dias_recentes'] ?? 7);

        foreach ($todas as $op) {
            if (!$mostrar_tudo && empty($filtros['data_inicio']) && empty($filtros['data_fim'])) {
                $data_op = $op['data_termino'] ?? $op['data_inicio'] ?? $op['data_criacao'] ?? null;
                if ($data_op) {
                    $timestamp_op = strtotime(str_replace('/', '-', substr($data_op, 0, 10)));
                    $timestamp_limite = strtotime("-$dias_recentes days");
                    if ($timestamp_op && $timestamp_op < $timestamp_limite) {
                        continue;
                    }
                }
            }

            if (!empty($filtros['data_inicio']) || !empty($filtros['data_fim'])) {
                $data_op = $op['data_termino'] ?? $op['data_inicio'] ?? $op['data_criacao'] ?? null;
                if (!$data_op) continue;

                $timestamp_op = strtotime(str_replace('/', '-', substr($data_op, 0, 10)));

                if (!empty($filtros['data_inicio'])) {
                    $ts_ini = strtotime(str_replace('/', '-', $filtros['data_inicio']));
                    if ($timestamp_op < $ts_ini) continue;
                }
                if (!empty($filtros['data_fim'])) {
                    $ts_fim = strtotime(str_replace('/', '-', $filtros['data_fim']) . ' 23:59:59');
                    if ($timestamp_op > $ts_fim) continue;
                }
            }

            if (!empty($filtros['booking'])) {
                $busca = strtoupper(trim($filtros['booking']));
                if (strpos(strtoupper($op['numero_booking'] ?? ''), $busca) === false) {
                    continue;
                }
            }

            if (!empty($filtros['status']) && $op['status'] !== $filtros['status']) {
                continue;
            }

            if (!empty($filtros['cliente'])) {
                if (($op['cliente_nome'] ?? '') !== $filtros['cliente']) {
                    continue;
                }
            }

            $resultado[] = $op;
        }

        return $resultado;
    }

    public function listarNotificacoes($usuario_id) {
        $resultado = [];
        foreach ($this->ler() as $op) {
            if (isset($op['notificacao_para']) && in_array($usuario_id, $op['notificacao_para'])) {
                $resultado[] = $op;
            }
        }
        return $resultado;
    }

    // ============================================================
    // CRIAÇÃO DE BOOKING
    // ============================================================

    public function criarBooking($dados, $supervisor) {
        $operacoes = $this->ler();

        $operacao = [
            'id' => $this->gerarId($operacoes),
            'numero_booking' => strtoupper(trim($dados['numero_booking'] ?? '')),
            'tipo_mercadoria' => trim($dados['tipo_mercadoria'] ?? ''),
            'cliente_id' => (int)($dados['cliente_id'] ?? 0),
            'cliente_nome' => trim($dados['cliente_nome'] ?? ''),
            'quantidade_estimada' => (int)($dados['quantidade_estimada'] ?? 0),
            'peso_estimado' => (float)($dados['peso_estimado'] ?? 0),
            'data_prevista' => trim($dados['data_prevista'] ?? ''),
            'observacoes_booking' => trim($dados['observacoes_booking'] ?? ''),
            'criado_por' => $supervisor['id'],
            'criado_por_nome' => $supervisor['nome'],
            'status' => 'Aberta',
            'data_criacao' => date('d/m/Y H:i'),
            'conferente_id' => null,
            'conferente_nome' => null,
            'data_inicio' => null,
            'data_termino' => null,
            'container' => null,
            'lacre' => null,
            'navio' => null,
            'armador' => null,
            'tipo_operacao' => 'armazem',
            'mao_de_obra' => 'terminal',
            'tara' => 0,
            'vgm' => 0,
            'itens' => [],
            'peso_bruto_total' => 0,
            'finalizada_por' => null,
            'finalizada_por_nome' => null,
            'reportado_pelo_adm' => false,
            'motivo_reporte' => null,
            'tipo_erro' => null,
            'notificacao_para' => [],
            'arquivada' => false,
            'arquivada_por' => null,
            'arquivada_por_nome' => null,
            'data_arquivamento' => null,
            'log' => [[
                'data' => date('d/m/Y H:i'),
                'usuario_id' => $supervisor['id'],
                'usuario_nome' => $supervisor['nome'],
                'acao' => 'Criou o Booking'
            ]]
        ];

        $operacoes[] = $operacao;
        $this->escrever($operacoes);
        return $operacao;
    }

    // ============================================================
    // CONFERENTE PEGA O BOOKING
    // ============================================================

    public function pegarOperacao($id_operacao, $conferente) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                if ($op['status'] != 'Aberta') return false;

                $op['status'] = 'Em Andamento';
                $op['conferente_id'] = $conferente['id'];
                $op['conferente_nome'] = $conferente['nome'];
                $op['data_inicio'] = date('d/m/Y H:i');

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $conferente['id'],
                    'usuario_nome' => $conferente['nome'],
                    'acao' => 'Pegou a operação'
                ];

                $this->escrever($operacoes);
                return true;
            }
        }
        return false;
    }

    public function registrarIngresso($id_operacao, $usuario) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $usuario['id'],
                    'usuario_nome' => $usuario['nome'],
                    'acao' => 'Ingressou na operação para auxiliar/terminar'
                ];
                $this->escrever($operacoes);
                return true;
            }
        }
        return false;
    }

    // ============================================================
    // ITENS
    // ============================================================

    public function adicionarItem($id_operacao, $item, $usuario) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $item['peso_total'] = (float)($item['quantidade'] ?? 0) * (float)($item['peso_unitario'] ?? 0);
                $item['status_conferencia'] = 'Pendente';
                $item['observacao'] = '';
                $op['itens'][] = $item;
                $op['peso_bruto_total'] += $item['peso_total'];

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $usuario['id'],
                    'usuario_nome' => $usuario['nome'],
                    'acao' => 'Adicionou item: ' . $item['descricao'] . ' (' . $item['quantidade'] . ' un.)'
                ];

                $this->escrever($operacoes);
                return true;
            }
        }
        return false;
    }

    public function removerItem($id_operacao, $indice, $usuario) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                if (isset($op['itens'][$indice])) {
                    $item_removido = $op['itens'][$indice];
                    $op['peso_bruto_total'] -= $item_removido['peso_total'];
                    unset($op['itens'][$indice]);
                    $op['itens'] = array_values($op['itens']);

                    $op['log'][] = [
                        'data' => date('d/m/Y H:i'),
                        'usuario_id' => $usuario['id'],
                        'usuario_nome' => $usuario['nome'],
                        'acao' => 'Removeu item: ' . $item_removido['descricao']
                    ];

                    $this->escrever($operacoes);
                    return true;
                }
            }
        }
        return false;
    }

    public function atualizarItem($id_operacao, $indice, $status, $observacao, $usuario) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                if (isset($op['itens'][$indice])) {
                    $op['itens'][$indice]['status_conferencia'] = $status;
                    $op['itens'][$indice]['observacao'] = $observacao;

                    $op['log'][] = [
                        'data' => date('d/m/Y H:i'),
                        'usuario_id' => $usuario['id'],
                        'usuario_nome' => $usuario['nome'],
                        'acao' => 'Conferiu item #' . ($indice + 1) . ' como ' . $status
                    ];

                    $this->escrever($operacoes);
                    return true;
                }
            }
        }
        return false;
    }

    public function atualizarDadosContainer($id_operacao, $dados, $usuario) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $op['container'] = strtoupper(trim($dados['container'] ?? ''));
                $op['lacre'] = strtoupper(trim($dados['lacre'] ?? ''));
                $op['navio'] = trim($dados['navio'] ?? '');
                $op['armador'] = trim($dados['armador'] ?? '');
                $op['tipo_operacao'] = $dados['tipo_operacao'] ?? 'armazem';
                $op['mao_de_obra'] = $dados['mao_de_obra'] ?? 'terminal';
                $op['tara'] = (float)($dados['tara'] ?? 0);
                $op['vgm'] = (float)($dados['vgm'] ?? 0);

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $usuario['id'],
                    'usuario_nome' => $usuario['nome'],
                    'acao' => 'Atualizou dados do container'
                ];

                $this->escrever($operacoes);
                return true;
            }
        }
        return false;
    }

    // ============================================================
    // FINALIZAR OPERAÇÃO
    // ============================================================

    public function finalizarOperacao($id_operacao, $conferente, $justificativa_pendencias = null) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $op['status'] = 'Finalizada';
                $op['data_termino'] = date('d/m/Y H:i');
                $op['finalizada_por'] = $conferente['id'];
                $op['finalizada_por_nome'] = $conferente['nome'];

                $acao = 'Finalizou a operação';
                if ($justificativa_pendencias) {
                    $acao .= ' (com pendências: ' . $justificativa_pendencias . ')';
                }

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $conferente['id'],
                    'usuario_nome' => $conferente['nome'],
                    'acao' => $acao
                ];

                $this->escrever($operacoes);
                return true;
            }
        }
        return false;
    }

    // ============================================================
    // REPORTAR ERRO
    // ============================================================

    public function reportarErro($id_operacao, $adm, $motivo, $tipo_erro) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $op['reportado_pelo_adm'] = true;
                $op['motivo_reporte'] = $motivo;
                $op['tipo_erro'] = $tipo_erro;

                if (isset($op['finalizada_por']) && !in_array($op['finalizada_por'], $op['notificacao_para'])) {
                    $op['notificacao_para'][] = $op['finalizada_por'];
                }

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $adm['id'],
                    'usuario_nome' => $adm['nome'],
                    'acao' => 'Reportou erro (' . $tipo_erro . '): ' . $motivo
                ];

                $this->escrever($operacoes);

                // REGISTRA E-MAIL SIMULADO PARA O CONFERENTE
                if (isset($op['finalizada_por'])) {
                    require_once __DIR__ . '/UsuarioRepository.php';
                    require_once __DIR__ . '/EmailRepository.php';
                    $repoUser = new UsuarioRepository();
                    $conferente = $repoUser->buscarPorId($op['finalizada_por']);
                    if ($conferente) {
                        $repoEmail = new EmailRepository();
                        $corpo = "Olá, {$conferente['nome']}!\n\n"
                                . "O ADM reportou um erro na operação Booking {$op['numero_booking']}.\n\n"
                                . "Tipo de erro: {$tipo_erro}\n"
                                . "Motivo: {$motivo}\n\n"
                                . "Acesse o sistema para revisar a operação e tomar as providências.\n\n"
                                . "Atenciosamente,\n"
                                . "Equipe LogiStock";
                        $repoEmail->registrar(
                            $conferente['email'],
                            $conferente['nome'],
                            "Erro reportado no Booking {$op['numero_booking']}",
                            $corpo,
                            'reporte_erro',
                            $id_operacao
                        );
                    }
                }

                return true;
            }
        }
        return false;
    }

    public function reportarErroNovamente($id_operacao, $adm, $motivo, $tipo_erro) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $op['reportado_pelo_adm'] = true;
                $op['motivo_reporte'] = $motivo;
                $op['tipo_erro'] = $tipo_erro;
                $op['justificado_pelo_conferente'] = false;
                $op['justificativa_conferente'] = null;

                if (isset($op['finalizada_por']) && !in_array($op['finalizada_por'], $op['notificacao_para'])) {
                    $op['notificacao_para'][] = $op['finalizada_por'];
                }

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $adm['id'],
                    'usuario_nome' => $adm['nome'],
                    'acao' => 'Reportou NOVAMENTE (' . $tipo_erro . '): ' . $motivo
                ];

                $this->escrever($operacoes);

                // REGISTRA E-MAIL SIMULADO NOVAMENTE
                if (isset($op['finalizada_por'])) {
                    require_once __DIR__ . '/UsuarioRepository.php';
                    require_once __DIR__ . '/EmailRepository.php';
                    $repoUser = new UsuarioRepository();
                    $conferente = $repoUser->buscarPorId($op['finalizada_por']);
                    if ($conferente) {
                        $repoEmail = new EmailRepository();
                        $corpo = "Olá, {$conferente['nome']}!\n\n"
                                . "O ADM reportou NOVAMENTE um erro na operação Booking {$op['numero_booking']}.\n\n"
                                . "Tipo de erro: {$tipo_erro}\n"
                                . "Motivo: {$motivo}\n\n"
                                . "Acesse o sistema para revisar.\n\n"
                                . "Atenciosamente,\n"
                                . "Equipe LogiStock";
                        $repoEmail->registrar(
                            $conferente['email'],
                            $conferente['nome'],
                            "Novo reporte no Booking {$op['numero_booking']}",
                            $corpo,
                            'reporte_erro',
                            $id_operacao
                        );
                    }
                }

                return true;
            }
        }
        return false;
    }

    public function reabrirOperacao($id_operacao, $conferente, $justificativa = '') {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $op['status'] = 'Em Andamento';
                $op['reportado_pelo_adm'] = false;
                $op['motivo_reporte'] = null;
                $op['tipo_erro'] = null;
                $op['notificacao_para'] = [];
                $op['justificado_pelo_conferente'] = false;
                $op['justificativa_conferente'] = null;
                $op['data_termino'] = null;
                $op['arquivada'] = false;
                $op['arquivada_por'] = null;
                $op['arquivada_por_nome'] = null;
                $op['data_arquivamento'] = null;

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $conferente['id'],
                    'usuario_nome' => $conferente['nome'],
                    'acao' => 'Reabriu a operação. Justificativa: ' . ($justificativa ?: 'Não informada')
                ];

                $this->escrever($operacoes);
                return true;
            }
        }
        return false;
    }

    public function marcarNotificacaoComoVista($id_operacao, $conferente_id) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                if (isset($op['notificacao_para'])) {
                    $op['notificacao_para'] = array_values(array_diff($op['notificacao_para'], [$conferente_id]));
                    $this->escrever($operacoes);
                    return true;
                }
            }
        }
        return false;
    }

    public function justificarOperacao($id_operacao, $conferente, $justificativa) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $op['justificado_pelo_conferente'] = true;
                $op['justificativa_conferente'] = $justificativa;
                $op['notificacao_para'] = [];

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $conferente['id'],
                    'usuario_nome' => $conferente['nome'],
                    'acao' => 'Justificou que a operação está correta: ' . $justificativa
                ];

                $this->escrever($operacoes);

                // REGISTRA E-MAIL SIMULADO PARA TODOS OS ADMs
                require_once __DIR__ . '/UsuarioRepository.php';
                require_once __DIR__ . '/EmailRepository.php';
                $repoUser = new UsuarioRepository();
                $adms = array_filter($repoUser->listarTodos(), function($u) {
                    return $u['perfil'] == 'adm' && !empty($u['ativo']);
                });
                $repoEmail = new EmailRepository();
                foreach ($adms as $adm) {
                    $corpo = "Olá, {$adm['nome']}!\n\n"
                            . "O conferente {$conferente['nome']} justificou que a operação Booking {$op['numero_booking']} está correta.\n\n"
                            . "Justificativa: {$justificativa}\n\n"
                            . "Acesse o sistema para revisar.\n\n"
                            . "Atenciosamente,\n"
                            . "Equipe LogiStock";
                    $repoEmail->registrar(
                        $adm['email'],
                        $adm['nome'],
                        "Justificativa recebida - Booking {$op['numero_booking']}",
                        $corpo,
                        'justificativa',
                        $id_operacao
                    );
                }

                return true;
            }
        }
        return false;
    }

    public function reabrirReporte($id_operacao, $adm) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $op['justificado_pelo_conferente'] = false;
                $op['justificativa_conferente'] = null;

                if (isset($op['finalizada_por']) && !in_array($op['finalizada_por'], $op['notificacao_para'])) {
                    $op['notificacao_para'][] = $op['finalizada_por'];
                }

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $adm['id'],
                    'usuario_nome' => $adm['nome'],
                    'acao' => 'Reabriu o reporte (não aceitou a justificativa do conferente)'
                ];

                $this->escrever($operacoes);
                return true;
            }
        }
        return false;
    }

    public function excluirOperacao($id_operacao, $usuario) {
        if (!in_array($usuario['perfil'], ['supervisor', 'adm'])) {
            return false;
        }

        $operacoes = $this->ler();
        foreach ($operacoes as $chave => $op) {
            if ($op['id'] == $id_operacao) {
                if ($op['status'] != 'Aberta') {
                    return false;
                }
                unset($operacoes[$chave]);
                $this->escrever(array_values($operacoes));
                return true;
            }
        }
        return false;
    }

    // ============================================================
    // ARQUIVAR / DESARQUIVAR
    // ============================================================

    public function arquivarOperacao($id_operacao, $adm) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $op['arquivada'] = true;
                $op['arquivada_por'] = $adm['id'];
                $op['arquivada_por_nome'] = $adm['nome'];
                $op['data_arquivamento'] = date('d/m/Y H:i');

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $adm['id'],
                    'usuario_nome' => $adm['nome'],
                    'acao' => 'Arquivou a operação (aprovada)'
                ];

                $this->escrever($operacoes);
                return true;
            }
        }
        return false;
    }

    public function desarquivarOperacao($id_operacao, $adm) {
        $operacoes = $this->ler();
        foreach ($operacoes as &$op) {
            if ($op['id'] == $id_operacao) {
                $op['arquivada'] = false;
                $op['arquivada_por'] = null;
                $op['arquivada_por_nome'] = null;
                $op['data_arquivamento'] = null;

                $op['log'][] = [
                    'data' => date('d/m/Y H:i'),
                    'usuario_id' => $adm['id'],
                    'usuario_nome' => $adm['nome'],
                    'acao' => 'Desarquivou a operação'
                ];

                $this->escrever($operacoes);
                return true;
            }
        }
        return false;
    }

    // ============================================================
    // ESTATÍSTICAS
    // ============================================================

    public function contarPorStatus() {
        $contagem = ['Aberta' => 0, 'Em Andamento' => 0, 'Finalizada' => 0];
        foreach ($this->ler() as $op) {
            if (isset($contagem[$op['status']])) {
                $contagem[$op['status']]++;
            }
        }
        return $contagem;
    }

    public function operacoesPorDia($dias = 7) {
        $resultado = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $dia = date('d/m', strtotime("-$i days"));
            $resultado[$dia] = 0;
        }

        foreach ($this->ler() as $op) {
            if (empty($op['data_termino'])) continue;
            $partes = explode(' ', $op['data_termino']);
            $data_str = $partes[0] ?? '';
            $sub = explode('/', $data_str);
            if (count($sub) >= 2) {
                $chave = $sub[0] . '/' . $sub[1];
                if (isset($resultado[$chave])) {
                    $resultado[$chave]++;
                }
            }
        }
        return $resultado;
    }

    public function topClientes($limite = 5) {
        $contagem = [];
        foreach ($this->ler() as $op) {
            $cliente = $op['cliente_nome'] ?? 'Sem cliente';
            if (!isset($contagem[$cliente])) $contagem[$cliente] = 0;
            $contagem[$cliente]++;
        }
        arsort($contagem);
        return array_slice($contagem, 0, $limite, true);
    }

    public function operacoesPorConferente() {
        $contagem = [];
        foreach ($this->ler() as $op) {
            $conf = $op['conferente_nome'] ?? 'Não atribuído';
            if (!isset($contagem[$conf])) $contagem[$conf] = 0;
            $contagem[$conf]++;
        }
        arsort($contagem);
        return $contagem;
    }
}
?>