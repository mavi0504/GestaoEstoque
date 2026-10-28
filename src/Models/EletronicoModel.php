<?php
// src/Models/EletronicoModel.php

require_once __DIR__ . '/../../config/database.php';

class EletronicoModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // ROTA 1: Listar eletrónicos ativos com saldos de estoque
    public function listarTodos() {
        $query = "SELECT 
                    e.id_eletronico,
                    e.nome,
                    e.descricao,
                    e.numero_lote,
                    e.fabricante,
                    e.categoria,
                    e.preco,
                    e.estoque_minimo,
                    e.ativo,
                    COALESCE(est.quantidade_atual, 0) AS saldo_estoque
                  FROM eletronicos e
                  LEFT JOIN estoque est ON e.id_eletronico = est.id_eletronico
                  WHERE e.ativo = 1
                  ORDER BY e.id_eletronico ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ROTA 2: Criar novo eletrónico e inicializar estoque
    public function criar(array $dados) {
        try {
            $this->conn->beginTransaction();

            // 1. Inserir o produto na tabela eletronicos
            $sql = "INSERT INTO eletronicos 
                    (nome, descricao, numero_lote, fabricante, categoria, tensao, conectividade, preco, estoque_minimo) 
                    VALUES 
                    (:nome, :descricao, :numero_lote, :fabricante, :categoria, :tensao, :conectividade, :preco, :estoque_minimo)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':descricao' => $dados['descricao'] ?? '',
                ':numero_lote' => $dados['numero_lote'],
                ':fabricante' => $dados['fabricante'],
                ':categoria' => $dados['categoria'],
                ':tensao' => $dados['tensao'] ?? null,
                ':conectividade' => $dados['conectividade'] ?? null,
                ':preco' => $dados['preco'],
                ':estoque_minimo' => $dados['estoque_minimo'] ?? 5
            ]);

            $idEletronico = $this->conn->lastInsertId();
            $qtdInicial = isset($dados['estoque_inicial']) ? (int)$dados['estoque_inicial'] : 0;

            // 2. Criar o registo inicial na tabela estoque
            $sqlEstoque = "INSERT INTO estoque (id_eletronico, quantidade_atual) VALUES (:id, :qtd)";
            $stmtEstoque = $this->conn->prepare($sqlEstoque);
            $stmtEstoque->execute([':id' => $idEletronico, ':qtd' => $qtdInicial]);

            // 3. Registar o histórico inicial na tabela movimentacoes se a quantidade for > 0
            if ($qtdInicial > 0) {
                $sqlMov = "INSERT INTO movimentacoes 
                            (id_eletronico, id_usuario, tipo, quantidade, saldo_anterior, saldo_posterior, observacao) 
                           VALUES 
                            (:id_eletronico, 1, 'ENTRADA', :qtd, 0, :qtd, 'Carga inicial de cadastro')";
                $stmtMov = $this->conn->prepare($sqlMov);
                $stmtMov->execute([':id_eletronico' => $idEletronico, ':qtd' => $qtdInicial]);
            }

            $this->conn->commit();
            return $idEletronico;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // ROTA 3: Atualizar dados de um eletrónico
    public function atualizar(array $dados) {
        $sql = "UPDATE eletronicos 
                SET nome = :nome, 
                    descricao = :descricao, 
                    fabricante = :fabricante, 
                    categoria = :categoria, 
                    preco = :preco, 
                    estoque_minimo = :estoque_minimo
                WHERE id_eletronico = :id AND ativo = 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $dados['id_eletronico'],
            ':nome' => $dados['nome'],
            ':descricao' => $dados['descricao'] ?? '',
            ':fabricante' => $dados['fabricante'],
            ':categoria' => $dados['categoria'],
            ':preco' => $dados['preco'],
            ':estoque_minimo' => $dados['estoque_minimo'] ?? 5
        ]);

        return $stmt->rowCount() > 0;
    }

    // ROTA 4: Excluir permanentemente do banco de dados (Exclusão Física)
    public function excluirFisico(int $id) {
        try {
            $this->conn->beginTransaction();

            // 1. Remover movimentações associadas
            $sqlMov = "DELETE FROM movimentacoes WHERE id_eletronico = :id";
            $stmtMov = $this->conn->prepare($sqlMov);
            $stmtMov->execute([':id' => $id]);

            // 2. Remover do estoque
            $sqlEst = "DELETE FROM estoque WHERE id_eletronico = :id";
            $stmtEst = $this->conn->prepare($sqlEst);
            $stmtEst->execute([':id' => $id]);

            // 3. Remover o produto da tabela eletronicos
            $sqlEle = "DELETE FROM eletronicos WHERE id_eletronico = :id";
            $stmtEle = $this->conn->prepare($sqlEle);
            $stmtEle->execute([':id' => $id]);

            $linhasAfetadas = $stmtEle->rowCount();

            $this->conn->commit();
            return $linhasAfetadas > 0;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}