<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

if (empty($_SESSION['usuario']['id'])) {
    $_SESSION['voltar_para'] = 'index.html#resumo-pedido';
    echo json_encode([
        'ok' => false,
        'precisa_login' => true,
        'redirect' => 'login.php',
        'erro' => 'Faça login ou cadastre-se para finalizar o pedido.'
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$usuarioId = (int) $_SESSION['usuario']['id'];
$clienteNome = trim((string) ($_SESSION['usuario']['nome'] ?? ($data['cliente_nome'] ?? '')));
$clienteEmail = trim((string) ($_SESSION['usuario']['email'] ?? ($data['cliente_email'] ?? '')));

if ($clienteEmail === '') {
    $clienteEmail = 'cliente@nekotempura.local';
}

$formaPagamento = trim((string) ($data['forma_pagamento'] ?? 'PIX'));
$metodosPermitidos = ['PIX', 'Cartao', 'Boleto'];

if (!in_array($formaPagamento, $metodosPermitidos, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Forma de pagamento inválida.']);
    exit;
}

$itens = $data['itens'] ?? [];

if (!is_array($itens) || count($itens) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Nenhum item recebido no pedido.']);
    exit;
}

$itensValidos = [];

foreach ($itens as $item) {
    if (!is_array($item)) {
        continue;
    }

    $produtoId = (int) ($item['produto_id'] ?? $item['id'] ?? 0);
    $qtd       = (int) ($item['quantidade'] ?? 0);
    $preco     = (float) ($item['preco_unitario'] ?? $item['preco'] ?? 0);

    if ($produtoId > 0 && $qtd > 0 && $preco >= 0) {
        $itensValidos[] = [
            'produto_id'     => $produtoId,
            'quantidade'     => $qtd,
            'preco_unitario' => $preco,
        ];
    }
}

if (count($itensValidos) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Itens inválidos no pedido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtBuscaProduto = $pdo->prepare('SELECT id, nome, preco, estoque FROM produtos WHERE id = :id FOR UPDATE');
    $stmtBaixaEstoque = $pdo->prepare('UPDATE produtos SET estoque = estoque - :qtd WHERE id = :id AND estoque >= :qtd');

    $totalPedido = 0.0;
    $itensConfirmados = [];

    foreach ($itensValidos as $item) {
        $stmtBuscaProduto->execute([':id' => $item['produto_id']]);
        $produto = $stmtBuscaProduto->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            throw new RuntimeException("Produto ID {$item['produto_id']} não encontrado.");
        }

        $qtd = $item['quantidade'];
        $estoqueAtual = (int) $produto['estoque'];

        if ($estoqueAtual < $qtd) {
            throw new RuntimeException("Estoque insuficiente para {$produto['nome']} (disponível: {$estoqueAtual}).");
        }

        $stmtBaixaEstoque->execute([
            ':qtd' => $qtd,
            ':id'  => $item['produto_id'],
        ]);

        if ($stmtBaixaEstoque->rowCount() === 0) {
            throw new RuntimeException("Falha ao dar baixa no estoque de {$produto['nome']}.");
        }

        $precoUnitario = (float) $produto['preco'];
        $totalPedido += $qtd * $precoUnitario;

        $itensConfirmados[] = [
            'produto_id'     => (int) $produto['id'],
            'quantidade'     => $qtd,
            'preco_unitario' => $precoUnitario,
        ];
    }

    $sqlPedido = "INSERT INTO pedidos (usuario_id, cliente_nome, cliente_email, total, forma_pagamento, status, criado_em)
                  VALUES (:usuario_id, :cliente_nome, :cliente_email, :total, :forma_pagamento, :status, NOW())";
    $stmtPedido = $pdo->prepare($sqlPedido);
    $stmtPedido->execute([
        ':usuario_id'      => $usuarioId,
        ':cliente_nome'    => $clienteNome !== '' ? $clienteNome : 'Cliente',
        ':cliente_email'   => $clienteEmail,
        ':total'           => $totalPedido,
        ':forma_pagamento' => $formaPagamento,
        ':status'          => 'confirmado',
    ]);

    $pedidoId = (int) $pdo->lastInsertId();

    $sqlItem = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario)
                VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario)";
    $stmtItem = $pdo->prepare($sqlItem);

    foreach ($itensConfirmados as $item) {
        $stmtItem->execute([
            ':pedido_id'      => $pedidoId,
            ':produto_id'     => $item['produto_id'],
            ':quantidade'     => $item['quantidade'],
            ':preco_unitario' => $item['preco_unitario'],
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'ok'              => true,
        'pedido_id'       => $pedidoId,
        'total'           => $totalPedido,
        'forma_pagamento' => $formaPagamento,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'ok'   => false,
        'erro' => $e->getMessage(),
    ]);
}