<?php
// src/Models/EstoqueModel.php

require_once __DIR__ . '/../../config/database.php';

class EstoqueModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // ROTA 5: Movimentar estoque (ENTRADA ou SAIDA)
    public function movimentar(int $idEletronico, string $tipo, int $quantidade, ?string $observacao = '') {
        try {
            $this->conn->beginTransaction();

            // 1. Obter saldo atual do produto
            $sqlAtual = "SELECT quantidade_atual FROM estoque WHERE id_eletronico = :id FOR UPDATE";
            $stmtAtual = $this->conn->prepare($sqlAtual);
            $stmtAtual->execute([':id' => $idEletronico]);
            $estoque = $stmtAtual->fetch(PDO::FETCH_ASSOC);

            if (!$estoque) {
                throw new Exception("PRODUTO_NAO_ENCONTRADO");
            }

            $saldoAnterior = (int)$estoque['quantidade_atual'];

            // 2. Validar saldo insuficiente em caso de saída
            if ($tipo === 'SAIDA' && $saldoAnterior < $quantidade) {
                throw new Exception("SALDO_INSUFICIENTE");
            }

            // 3. Calcular novo saldo
            $saldoPosterior = ($tipo === 'ENTRADA') 
                ? $saldoAnterior + $quantidade 
                : $saldoAnterior - $quantidade;

            // 4. Atualizar saldo no estoque
            $sqlUp = "UPDATE estoque SET quantidade_atual = :novoSaldo WHERE id_eletronico = :id";
            $stmtUp = $this->conn->prepare($sqlUp);
            $stmtUp->execute([':novoSaldo' => $saldoPosterior, ':id' => $idEletronico]);

            // 5. Inserir histórico de movimentação (assumindo id_usuario = 1 do admin)
            $sqlMov = "INSERT INTO movimentacoes 
                        (id_eletronico, id_usuario, tipo, quantidade, saldo_anterior, saldo_posterior, observacao) 
                       VALUES 
                        (:id_eletronico, 1, :tipo, :quantidade, :saldo_anterior, :saldo_posterior, :observacao)";
            $stmtMov = $this->conn->prepare($sqlMov);
            $stmtMov->execute([
                ':id_eletronico' => $idEletronico,
                ':tipo' => $tipo,
                ':quantidade' => $quantidade,
                ':saldo_anterior' => $saldoAnterior,
                ':saldo_posterior' => $saldoPosterior,
                ':observacao' => $observacao
            ]);

            $this->conn->commit();
            return [
                "saldo_anterior" => $saldoAnterior,
                "saldo_atual" => $saldoPosterior
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // ROTA 6: Obter histórico de movimentações
    public function obterHistorico(?int $idEletronico = null) {
        $sql = "SELECT 
                    m.id_movimentacao,
                    m.id_eletronico,
                    e.nome AS produto,
                    m.tipo,
                    m.quantidade,
                    m.saldo_anterior,
                    m.saldo_posterior,
                    m.observacao,
                    m.data_movimentacao
                FROM movimentacoes m
                INNER JOIN eletronicos e ON m.id_eletronico = e.id_eletronico";

        if ($idEletronico) {
            $sql .= " WHERE m.id_eletronico = :id";
        }

        $sql .= " ORDER BY m.data_movimentacao DESC";

        $stmt = $this->conn->prepare($sql);
        if ($idEletronico) {
            $stmt->execute([':id' => $idEletronico]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}