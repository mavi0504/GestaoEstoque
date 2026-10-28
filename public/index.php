<?php
// public/index.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Views/JsonView.php';
require_once __DIR__ . '/../src/Controllers/EletronicoController.php';
require_once __DIR__ . '/../src/Controllers/EstoqueController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$basePath = dirname($_SERVER['SCRIPT_NAME']);
$route = str_replace($basePath, '', $uri);
$route = rtrim($route, '/');
if (empty($route)) {
    $route = '/';
}

// ROTA HEALTH CHECK
if ($route === '/health' && $method === 'GET') {
    try {
        $conn = Database::getConnection();
        $stmt = $conn->query("SHOW TABLES");
        $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        JsonView::render([
            "status" => "OK",
            "mensagem" => "API e Banco de Dados conectados com sucesso!",
            "tabelas" => $tabelas
        ], 200);
    } catch (Exception $e) {
        JsonView::render(["status" => "ERRO", "mensagem" => $e->getMessage()], 500);
    }
}

// ROTAS DE ELETRÔNICOS
if ($route === '/eletronico') {
    $controller = new EletronicoController();
    
    if ($method === 'GET') $controller->listar();
    if ($method === 'POST') $controller->criar();
    if ($method === 'PUT') $controller->atualizar();
    if ($method === 'DELETE') $controller->excluir();
}

// ROTA 5: PUT /estoque (Movimentar estoque)
if ($route === '/estoque' && $method === 'PUT') {
    $controller = new EstoqueController();
    $controller->movimentar();
}

// ROTA 6: GET /movimentacoes (Histórico)
if ($route === '/movimentacoes' && $method === 'GET') {
    $controller = new EstoqueController();
    $controller->historico();
}

// Rota 404 Padrão
JsonView::render(["erro" => "Rota não encontrada"], 404);