<?php
// src/Controllers/EstoqueController.php

require_once __DIR__ . '/../Models/EstoqueModel.php';
require_once __DIR__ . '/../Views/JsonView.php';

class EstoqueController {
    private $model;

    public function __construct() {
        $this->model = new EstoqueModel();
    }

    // ROTA 5: PUT /estoque
    public function movimentar() {
        $body = json_decode(file_get_contents('php://input'), true);

        if (empty($body['id_eletronico']) || empty($body['tipo']) || !isset($body['quantidade'])) {
            JsonView::render(["erro" => "Campos obrigatórios ausentes (id_eletronico, tipo, quantidade)"], 400);
        }

        $tipo = strtoupper($body['tipo']);
        if (!in_array($tipo, ['ENTRADA', 'SAIDA'])) {
            JsonView::render(["erro" => "O tipo deve ser 'ENTRADA' ou 'SAIDA'"], 400);
        }

        $quantidade = (int)$body['quantidade'];
        if ($quantidade <= 0) {
            JsonView::render(["erro" => "A quantidade deve ser superior a zero"], 400);
        }

        try {
            $resultado = $this->model->movimentar(
                (int)$body['id_eletronico'],
                $tipo,
                $quantidade,
                $body['observacao'] ?? ''
            );

            JsonView::render([
                "mensagem" => "Movimentação registrada com sucesso!",
                "dados" => $resultado
            ], 200);
        } catch (Exception $e) {
            if ($e->getMessage() === "PRODUTO_NAO_ENCONTRADO") {
                JsonView::render(["erro" => "Produto não encontrado no estoque"], 404);
            } elseif ($e->getMessage() === "SALDO_INSUFICIENTE") {
                JsonView::render(["erro" => "Saldo insuficiente para realizar a saída"], 422);
            } else {
                JsonView::render(["erro" => "Erro ao processar movimentação"], 500);
            }
        }
    }

    // ROTA 6: GET /movimentacoes
    public function historico() {
        $idEletronico = isset($_GET['id_eletronico']) ? (int)$_GET['id_eletronico'] : null;
        $dados = $this->model->obterHistorico($idEletronico);
        JsonView::render($dados, 200);
    }
}