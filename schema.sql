CREATE DATABASE IF NOT EXISTS neko_tempura
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE neko_tempura;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    tipo ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(140) NOT NULL,
    categoria VARCHAR(60) NOT NULL,
    descricao TEXT NULL,
    preco DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    estoque INT NOT NULL DEFAULT 0,
    imagem VARCHAR(255) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    cliente_nome VARCHAR(120) NOT NULL,
    cliente_email VARCHAR(160) NOT NULL,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    forma_pagamento VARCHAR(20) NOT NULL DEFAULT 'PIX',
    status VARCHAR(30) NOT NULL DEFAULT 'confirmado',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedidos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS itens_pedido (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_itens_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_itens_produto
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO produtos (nome, categoria, descricao, preco, estoque, imagem, ativo) VALUES
('Combo sushi da casa (16 peças)', 'sushis', 'Salmão, uramaki e hossomaki feitos na hora.', 42.90, 15, '7ae101c6-91cd-4ec1-8507-595675a9d40f.jpg', 1),
('Temaki de salmão', 'temakis', 'Alga, arroz temperado, salmão e cebolinha.', 26.50, 12, '18aeaa31-b2f0-4aa0-92fc-07b899fea5e2.jpg', 1),
('Tempurá crocante', 'tempura', 'Camarão e legumes empanados com molho da casa.', 34.00, 10, 'f9696b17-1268-4dda-8c4d-cf01a0fc84a8.jpg', 1),
('Lámen da casa', 'lamen', 'Caldo quente, macarrão, ovo marinado e vegetais.', 38.00, 8, 'fea1b850-abe0-44a0-9fbe-c4a76bc4f808.jpg', 1),
('Yakisoba na chapa', 'yakisoba', 'Macarrão, legumes variados e molho shoyu.', 35.90, 14, 'e81cf581-73cd-43a7-b065-4de7b7172f0f.jpg', 1),
('Guioza (6 unidades)', 'guioza', 'Pastéis selados na chapa para compartilhar.', 22.00, 20, '667152ea-d072-44a9-a40d-692ecd120c7d.jpg', 1)
ON DUPLICATE KEY UPDATE nome = VALUES(nome);