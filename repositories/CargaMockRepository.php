<?php
// Aqui estão os dados FALSOS que você vai usar no PC do trabalho.
// Depois, em casa, a gente cria o CargaDBRepository que puxa do MySQL.
class CargaMockRepository {

    // Método que lista todas as cargas com os tipos que falamos
    public function listarTodas() {
        return [
            ['id' => 1, 'nome' => 'Soja em Grãos', 'categoria' => 'Grãos', 'quantidade_esperada' => 1000, 'lote' => 'LOT-2024-A1'],
            ['id' => 2, 'nome' => 'Madeira Serrada Pinus', 'categoria' => 'Madeira', 'quantidade_esperada' => 500, 'lote' => 'LOT-2024-B2'],
            ['id' => 3, 'nome' => 'Placa Mãe Eletrônica', 'categoria' => 'Eletrônicos', 'quantidade_esperada' => 200, 'lote' => 'LOT-2024-C3'],
            ['id' => 4, 'nome' => 'Arroz Beneficiado', 'categoria' => 'Alimentos', 'quantidade_esperada' => 800, 'lote' => 'LOT-2024-D4'],
            ['id' => 5, 'nome' => 'Milho Exportação', 'categoria' => 'Grãos', 'quantidade_esperada' => 1500, 'lote' => 'LOT-2024-E5'],
            ['id' => 6, 'nome' => 'Container Madeira Nórdica', 'categoria' => 'Madeira', 'quantidade_esperada' => 300, 'lote' => 'LOT-2024-F6'],
        ];
    }

    // Método para buscar uma carga específica por ID (vamos usar depois)
    public function buscarPorId($id) {
        $todas = $this->listarTodas();
        foreach ($todas as $item) {
            if ($item['id'] == $id) {
                return $item;
            }
        }
        return null;
    }
}
?>