<?php
// config/database.php

class Database {
    private static $host = '127.0.0.1';
    private static $db_name = 'gestao_estoque_eletro';
    private static $username = 'root'; // Ajusta se o teu utilizador for diferente
    private static $password = '1234';     // Coloca a tua senha local do MySQL

    public static function getConnection() {
        $conn = null;
        try {
            $conn = new PDO(
                "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4",
                self::$username,
                self::$password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["erro" => "Erro na conexão com o banco de dados: " . $e->getMessage()]);
            exit;
        }
        return $conn;
    }
}