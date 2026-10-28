<?php
// src/Controllers/EletronicoController.php

require_once __DIR__ . '/../Models/EletronicoModel.php';
require_once __DIR__ . '/../Views/JsonView.php';

class EletronicoController {
    private $model;

    public function __construct() {
        $this->model = new EletronicoModel();
    }

    // ROTA 1: Listar eletrónicos
    public function listar() {
        $dados = $this->model->listarTodos();
        JsonView::render($dados, 200);
    }

    // ROTA 2: Criar novo eletrónico
    public function criar() {
        $body = json_decode(file_get_contents('php://input'), true);

        // Validação de campos obrigatórios
        if (empty($body['nome']) || empty($body['numero_lote']) || empty($body['fabricante']) || empty($body['categoria']) || !isset($body['preco'])) {
            JsonView::render(["erro" => "Campos obrigatórios ausentes (nome, numero_lote, fabricante, categoria, preco)"], 400);
        }

        try {
            $idCriado = $this->model->criar($body);
            JsonView::render([
                "mensagem" => "Eletrónico cadastrado com sucesso!",
                "id_eletronico" => $idCriado
            ], 201);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                JsonView::render(["erro" => "Número de lote já cadastrado no sistema"], 409);
            }
            JsonView::render(["erro" => "Erro interno ao cadastrar produto"], 500);
        }
    }

    // ROTA 3: Atualizar dados de um eletrónico
    public function atualizar() {
        $body = json_decode(file_get_contents('php://input'), true);

        if (empty($body['id_eletronico'])) {
            JsonView::render(["erro" => "O campo id_eletronico é obrigatório para atualização"], 400);
        }

        $sucesso = $this->model->atualizar($body);
        
        if ($sucesso) {
            JsonView::render(["mensagem" => "Dados do eletrónico atualizados com sucesso!"], 200);
        } else {
            JsonView::render(["erro" => "Eletrónico não encontrado ou inativo"], 404);
        }
    }

    // ROTA 4: Excluir permanentemente do banco de dados (Exclusão Física)
    public function excluir() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            JsonView::render(["erro" => "O parâmetro ID é obrigatório ex: /eletronico?id=1"], 400);
        }

        try {
            $sucesso = $this->model->excluirFisico($id);

            if ($sucesso) {
                JsonView::render(["mensagem" => "Eletrónico removido permanentemente da base de dados!"], 200);
            } else {
                JsonView::render(["erro" => "Eletrónico não encontrado"], 404);
            }
        } catch (Exception $e) {
            JsonView::render(["erro" => "Erro interno ao excluir o produto da base de dados"], 500);
        }
    }
}