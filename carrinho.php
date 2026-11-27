<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

function responderCarrinho(PDO $pdo, array $extra = []): void
{
    $itens = [];
    $total = 0.0;
    $quantidadeTotal = 0;

    foreach ($_SESSION['carrinho'] as $prodId => $qtd) {
        $prodId = (int) $prodId;
        $qtd = (int) $qtd;
        if ($prodId <= 0 || $qtd <= 0) {
            continue;
        }

        $stmt = $pdo->prepare('SELECT id, nome, preco, estoque, imagem FROM produtos WHERE id = :id');
        $stmt->execute([':id' => $prodId]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            unset($_SESSION['carrinho'][$prodId]);
            continue;
        }

        $estoque = (int) $prod['estoque'];
        if ($estoque <= 0) {
            unset($_SESSION['carrinho'][$prodId]);
            continue;
        }

        if ($qtd > $estoque) {
            $qtd = $estoque;
            $_SESSION['carrinho'][$prodId] = $qtd;
        }

        $preco = (float) $prod['preco'];
        $subtotal = round($preco * $qtd, 2);
        $total += $subtotal;
        $quantidadeTotal += $qtd;

        $itens[] = [
            'produto_id'     => (int) $prod['id'],
            'nome'           => (string) $prod['nome'],
            'preco_unitario' => $preco,
            'quantidade'     => $qtd,
            'estoque'        => $estoque,
            'subtotal'       => $subtotal,
            'imagem'         => (string) ($prod['imagem'] ?? ''),
        ];
    }

    echo json_encode(array_merge([
        'ok'               => true,
        'itens'            => $itens,
        'total'            => round($total, 2),
        'quantidade_total' => $quantidadeTotal,
    ], $extra));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    responderCarrinho($pdo);
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$acao = (string) ($input['acao'] ?? 'get');
$produtoId = (int) ($input['produto_id'] ?? $input['id'] ?? 0);

try {
    if ($acao === 'limpar') {
        $_SESSION['carrinho'] = [];
        responderCarrinho($pdo);
    }

    if ($acao === 'sincronizar' && is_array($input['itens'] ?? null)) {
        $_SESSION['carrinho'] = [];
        foreach ($input['itens'] as $item) {
            if (!is_array($item)) continue;
            $pid = (int) ($item['produto_id'] ?? $item['id'] ?? 0);
            $q = (int) ($item['quantidade'] ?? 0);
            if ($pid > 0 && $q > 0) {
                $stmt = $pdo->prepare('SELECT estoque FROM produtos WHERE id = :id');
                $stmt->execute([':id' => $pid]);
                $est = (int) ($stmt->fetchColumn() ?? 0);
                if ($est > 0) {
                    $_SESSION['carrinho'][$pid] = min($q, $est);
                }
            }
        }
        responderCarrinho($pdo);
    }

    if ($acao === 'remover') {
        if ($produtoId > 0) {
            unset($_SESSION['carrinho'][$produtoId]);
        }
        responderCarrinho($pdo);
    }

    if ($acao === 'adicionar' || $acao === 'alterar_quantidade') {
        if ($produtoId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'erro' => 'Produto inválido.']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, nome, estoque FROM produtos WHERE id = :id');
        $stmt->execute([':id' => $produtoId]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'erro' => 'Produto não encontrado.']);
            exit;
        }

        $estoque = (int) $prod['estoque'];
        $atual = (int) ($_SESSION['carrinho'][$produtoId] ?? 0);

        if ($acao === 'adicionar') {
            $delta = (int) ($input['quantidade'] ?? 1);
            $novaQtd = $atual + max(1, $delta);
        } else {
            $novaQtd = (int) ($input['quantidade'] ?? 1);
        }

        if ($novaQtd <= 0) {
            unset($_SESSION['carrinho'][$produtoId]);
            responderCarrinho($pdo);
        }

        if ($novaQtd > $estoque) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'erro' => "Estoque disponível para {$prod['nome']}: {$estoque} unidade(s)."
            ]);
            exit;
        }

        $_SESSION['carrinho'][$produtoId] = $novaQtd;
        responderCarrinho($pdo);
    }

    responderCarrinho($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro ao atualizar carrinho na sessão.']);
}