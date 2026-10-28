CREATE DATABASE IF NOT EXISTS gestao_estoque_eletro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gestao_estoque_eletro;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS movimentacoes;
DROP TABLE IF EXISTS estoque;
DROP TABLE IF EXISTS eletronicos;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('ADMIN', 'OPERADOR') NOT NULL DEFAULT 'OPERADOR',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE eletronicos (
    id_eletronico INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    numero_lote VARCHAR(80) NOT NULL UNIQUE,
    fabricante VARCHAR(120) NOT NULL,
    categoria VARCHAR(80) NOT NULL,
    tensao VARCHAR(60) NULL,
    dimensoes VARCHAR(100) NULL,
    resolucao VARCHAR(100) NULL,
    capacidade VARCHAR(100) NULL,
    conectividade VARCHAR(150) NULL,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    processador VARCHAR(120) NULL,
    memoria VARCHAR(120) NULL,
    camera VARCHAR(120) NULL,
    sistema_operacional VARCHAR(120) NULL,
    cor VARCHAR(80) NULL,
    estoque_minimo INT UNSIGNED NOT NULL DEFAULT 5,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_eletronicos_nome (nome),
    INDEX idx_eletronicos_lote (numero_lote),
    INDEX idx_eletronicos_ativo (ativo)
) ENGINE=InnoDB;

CREATE TABLE estoque (
    id_estoque INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_eletronico INT UNSIGNED NOT NULL UNIQUE,
    quantidade_atual INT UNSIGNED NOT NULL DEFAULT 0,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_estoque_eletronico
        FOREIGN KEY (id_eletronico) REFERENCES eletronicos(id_eletronico)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE movimentacoes (
    id_movimentacao BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_eletronico INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    tipo ENUM('ENTRADA', 'SAIDA') NOT NULL,
    quantidade INT UNSIGNED NOT NULL,
    saldo_anterior INT UNSIGNED NOT NULL,
    saldo_posterior INT UNSIGNED NOT NULL,
    observacao VARCHAR(255) NULL,
    data_movimentacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_movimentacoes_eletronico (id_eletronico),
    INDEX idx_movimentacoes_usuario (id_usuario),
    INDEX idx_movimentacoes_data (data_movimentacao),
    CONSTRAINT fk_movimentacao_eletronico
        FOREIGN KEY (id_eletronico) REFERENCES eletronicos(id_eletronico)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_movimentacao_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO usuarios (nome, email, senha_hash, perfil)
VALUES
('Administrador', 'admin@estoque.local', '$2y$12$0umOgTw9P/9fj5MIaOyyXO6DWYr9aM6ujMfqFAmw9l1RuRfx593cS', 'ADMIN'),
('Operador', 'operador@estoque.local', '$2y$12$.8Z/4c9YXvCXhXmW6eLm.OqzJTwsqR/YIYzx8iBetRM7upDOZRhOy', 'OPERADOR');

INSERT INTO eletronicos
    (nome, descricao, numero_lote, fabricante, categoria, tensao, conectividade, preco,
     processador, memoria, sistema_operacional, cor, estoque_minimo)
VALUES
('Notebook Vision 14', 'Notebook de uso geral para estudo e escritório.', 'LOTE-NB-001',
 'TechVision', 'Notebook', '110/220V', 'Wi-Fi 6, Bluetooth 5.2', 3299.90,
 'Intel Core i5', '16 GB RAM / SSD 512 GB', 'Linux', 'Cinza', 3),
('Smartphone Connect X', 'Smartphone intermediário com conectividade 5G.', 'LOTE-SP-001',
 'Connect', 'Smartphone', '5V', '5G, Wi-Fi, Bluetooth', 1899.00,
 'Octa-core', '8 GB RAM / 256 GB', 'Android', 'Preto', 5),
('Cabo HDMI 2m', 'Cabo HDMI de 2 metros para áudio e vídeo digital.', 'LOTE-AC-001',
 'WirePlus', 'Acessório', NULL, 'HDMI', 39.90,
 NULL, NULL, NULL, 'Preto', 10);

INSERT INTO estoque (id_eletronico, quantidade_atual)
VALUES
(1, 8),
(2, 4),
(3, 25);

INSERT INTO movimentacoes
    (id_eletronico, id_usuario, tipo, quantidade, saldo_anterior, saldo_posterior, observacao)
VALUES
(1, 1, 'ENTRADA', 8, 0, 8, 'Carga inicial'),
(2, 1, 'ENTRADA', 4, 0, 4, 'Carga inicial'),
(3, 1, 'ENTRADA', 25, 0, 25, 'Carga inicial');