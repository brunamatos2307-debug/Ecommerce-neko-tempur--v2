<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexao.php';

try {
    $categoria = trim((string) ($_GET['categoria'] ?? ''));
    $busca     = trim((string) ($_GET['busca'] ?? ''));
    $id        = (int) ($_GET['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT id, nome, categoria, descricao, preco, estoque, imagem FROM produtos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'erro' => 'Produto não encontrado.']);
            exit;
        }

        echo json_encode(['ok' => true, 'produto' => $produto]);
        exit;
    }

    $sql = 'SELECT id, nome, categoria, descricao, preco, estoque, imagem FROM produtos WHERE 1=1';
    $params = [];

    if ($categoria !== '' && strtolower($categoria) !== 'todos') {
        $sql .= ' AND categoria = :categoria';
        $params[':categoria'] = $categoria;
    }

    if ($busca !== '') {
        $sql .= ' AND (nome LIKE :busca OR descricao LIKE :busca)';
        $params[':busca'] = '%' . $busca . '%';
    }

    $sql .= ' ORDER BY id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'produtos' => $produtos]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro ao buscar produtos.']);
}