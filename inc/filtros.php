<?php
/**
 * Componente reutilizável de filtros.
 * 
 * Espera as variáveis:
 * - $filtros_permitidos: array com os filtros que devem aparecer (ex: ['data','booking','status','cliente'])
 * - $clientes_lista: array de clientes (se 'cliente' estiver nos filtros permitidos)
 * - $valores_atuais: array com os valores atuais dos filtros (para manter o estado)
 * - $url_base: URL do dashboard atual (ex: 'dashboard_adm.php')
 */
if (!isset($filtros_permitidos)) $filtros_permitidos = [];
if (!isset($valores_atuais)) $valores_atuais = [];
if (!isset($url_base)) $url_base = '#';
if (!isset($clientes_lista)) $clientes_lista = [];

// Verifica se está em modo "histórico completo"
$historico_completo = !empty($valores_atuais['mostrar_tudo']);
?>

<div class="barra-filtros">
    <form method="GET" action="<?php echo $url_base; ?>">
        <div class="filtros-linha">
            <?php if (in_array('data', $filtros_permitidos)): ?>
                <div>
                    <label for="data_inicio">De:</label>
                    <input type="text" id="data_inicio" name="data_inicio" placeholder="dd/mm/aaaa" 
                           value="<?php echo htmlspecialchars($valores_atuais['data_inicio'] ?? ''); ?>" maxlength="10">
                </div>
                <div>
                    <label for="data_fim">Até:</label>
                    <input type="text" id="data_fim" name="data_fim" placeholder="dd/mm/aaaa" 
                           value="<?php echo htmlspecialchars($valores_atuais['data_fim'] ?? ''); ?>" maxlength="10">
                </div>
            <?php endif; ?>

            <?php if (in_array('booking', $filtros_permitidos)): ?>
                <div>
                    <label for="booking">Booking:</label>
                    <input type="text" id="booking" name="booking" placeholder="Ex: BKG-001" 
                           value="<?php echo htmlspecialchars($valores_atuais['booking'] ?? ''); ?>" maxlength="30">
                </div>
            <?php endif; ?>

            <?php if (in_array('status', $filtros_permitidos)): ?>
                <div>
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="">Todos</option>
                        <option value="Aberta" <?php echo ($valores_atuais['status'] ?? '') == 'Aberta' ? 'selected' : ''; ?>>Aberta</option>
                        <option value="Em Andamento" <?php echo ($valores_atuais['status'] ?? '') == 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="Finalizada" <?php echo ($valores_atuais['status'] ?? '') == 'Finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array('cliente', $filtros_permitidos) && count($clientes_lista) > 0): ?>
                <div>
                    <label for="cliente">Cliente:</label>
                    <select id="cliente" name="cliente">
                        <option value="">Todos</option>
                        <?php foreach ($clientes_lista as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['nome']); ?>" 
                                    <?php echo ($valores_atuais['cliente'] ?? '') == $c['nome'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="filtros-botoes">
                <button type="submit" class="btn-filtrar">🔍 Filtrar</button>
                <a href="<?php echo $url_base; ?>" class="btn-limpar">✖ Limpar</a>
            </div>
        </div>

        <!-- Botão histórico completo -->
        <div class="filtros-extra">
            <?php if ($historico_completo): ?>
                <a href="<?php echo $url_base; ?>" class="btn-historico ativo">
                    📅 Voltar para últimos 7 dias
                </a>
            <?php else: ?>
                <a href="<?php echo $url_base; ?>?mostrar_tudo=1" class="btn-historico">
                    📜 Ver Histórico Completo
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>