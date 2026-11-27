<?php
declare(strict_types=1);

require_once __DIR__ . '/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tipoUsuario = $_SESSION['usuario']['tipo'] ?? $_SESSION['usuario']['papel'] ?? '';
if (empty($_SESSION['usuario']['id']) || strtolower((string) $tipoUsuario) !== 'admin') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Acesso Restrito - Neko Tempurá</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="container py-5">
        <div class="alert alert-danger">
            <h4>Acesso restrito a administradores</h4>
            <p class="mb-3">Você precisa estar logado como <strong>admin</strong> para acessar o painel.</p>
            <a href="login.php" class="btn btn-dark">Ir para login</a>
            <a href="index.html" class="btn btn-outline-secondary">Voltar ao cardápio</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$mensagem = '';
$erro = '';

// Verifica se a coluna 'ativo' existe na tabela produtos
$temColunaAtivo = false;
try {
    $check = $pdo->query("SHOW COLUMNS FROM produtos LIKE 'ativo'");
    $temColunaAtivo = (bool) $check->fetch();
} catch (Throwable $e) {
    $temColunaAtivo = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string) ($_POST['acao'] ?? '');

    try {
        if ($acao === 'criar') {
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $categoria = trim((string) ($_POST['categoria'] ?? ''));
            $descricao = trim((string) ($_POST['descricao'] ?? ''));
            $preco = (float) ($_POST['preco'] ?? 0);
            $estoque = (int) ($_POST['estoque'] ?? 0);
            $imagem = trim((string) ($_POST['imagem'] ?? ''));

            if ($nome === '' || $categoria === '' || $preco < 0 || $estoque < 0) {
                throw new RuntimeException('Preencha nome, categoria, preço e estoque válidos.');
            }

            if ($temColunaAtivo) {
                $stmt = $pdo->prepare(
                    'INSERT INTO produtos (nome, categoria, descricao, preco, estoque, imagem, ativo)
                     VALUES (:nome, :categoria, :descricao, :preco, :estoque, :imagem, 1)'
                );
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO produtos (nome, categoria, descricao, preco, estoque, imagem)
                     VALUES (:nome, :categoria, :descricao, :preco, :estoque, :imagem)'
                );
            }

            $stmt->execute([
                ':nome'      => $nome,
                ':categoria' => $categoria,
                ':descricao' => $descricao,
                ':preco'     => $preco,
                ':estoque'   => $estoque,
                ':imagem'    => $imagem !== '' ? $imagem : null,
            ]);

            $mensagem = 'Produto cadastrado com sucesso.';
        } elseif ($acao === 'editar') {
            $id = (int) ($_POST['id'] ?? 0);
            $preco = (float) ($_POST['preco'] ?? 0);
            $estoque = (int) ($_POST['estoque'] ?? 0);
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if ($id <= 0 || $preco < 0 || $estoque < 0) {
                throw new RuntimeException('Dados inválidos para edição.');
            }

            if ($temColunaAtivo) {
                $stmt = $pdo->prepare('UPDATE produtos SET preco = :preco, estoque = :estoque, ativo = :ativo WHERE id = :id');
                $stmt->execute([':preco' => $preco, ':estoque' => $estoque, ':ativo' => $ativo, ':id' => $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE produtos SET preco = :preco, estoque = :estoque WHERE id = :id');
                $stmt->execute([':preco' => $preco, ':estoque' => $estoque, ':id' => $id]);
            }

            $mensagem = "Produto #{$id} atualizado.";
        } elseif ($acao === 'desativar' || $acao === 'excluir') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('ID de produto inválido.');
            }

            if ($acao === 'desativar' && $temColunaAtivo) {
                $stmt = $pdo->prepare('UPDATE produtos SET ativo = 0 WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $mensagem = "Produto #{$id} desativado.";
            } else {
                $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $mensagem = "Produto #{$id} excluído.";
            }
        }
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}

$produtos = $pdo->query('SELECT * FROM produtos ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

$pedidos = $pdo->query('SELECT * FROM pedidos ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$stmtItens = $pdo->prepare(
    'SELECT ip.quantidade, ip.preco_unitario, p.nome AS produto_nome
     FROM itens_pedido ip
     LEFT JOIN produtos p ON p.id = ip.produto_id
     WHERE ip.pedido_id = :pedido_id'
);
foreach ($pedidos as &$ped) {
    $stmtItens->execute([':pedido_id' => $ped['id']]);
    $ped['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
}
unset($ped);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Neko Tempurá</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Painel Administrativo • Neko Tempurá</h1>
        <a href="index.html" class="btn btn-outline-dark btn-sm">Ver loja</a>
    </div>

    <?php if ($mensagem !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- Cadastrar produto -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Novo Produto</h2>
            <form method="post" class="row g-2">
                <input type="hidden" name="acao" value="criar">
                <div class="col-md-3">
                    <input type="text" name="nome" class="form-control" placeholder="Nome do prato" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="categoria" class="form-control" placeholder="Categoria (ex.: sushis)" required>
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.01" min="0" name="preco" class="form-control" placeholder="Preço" required>
                </div>
                <div class="col-md-2">
                    <input type="number" min="0" name="estoque" class="form-control" placeholder="Estoque" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="imagem" class="form-control" placeholder="Arquivo de imagem (ex.: prato.jpg)">
                </div>
                <div class="col-md-9">
                    <input type="text" name="descricao" class="form-control" placeholder="Descrição curta">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-dark w-100">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- CRUD de produtos -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Produtos Cadastrados</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Preço (R$)</th>
                            <th>Estoque</th>
                            <?php if ($temColunaAtivo): ?><th>Ativo</th><?php endif; ?>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($produtos as $prod): ?>
                        <tr>
                            <td><?= (int) $prod['id'] ?></td>
                            <td><strong><?= htmlspecialchars((string) $prod['nome'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars((string) $prod['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="min-width: 220px;">
                                <form method="post" class="d-flex gap-1 align-items-center">
                                    <input type="hidden" name="acao" value="editar">
                                    <input type="hidden" name="id" value="<?= (int) $prod['id'] ?>">
                                    <input type="number" step="0.01" min="0" name="preco"
                                           value="<?= htmlspecialchars((string) $prod['preco'], ENT_QUOTES, 'UTF-8') ?>"
                                           class="form-control form-control-sm" style="width: 90px;">
                                    <input type="number" min="0" name="estoque"
                                           value="<?= (int) $prod['estoque'] ?>"
                                           class="form-control form-control-sm" style="width: 75px;">
                                    <?php if ($temColunaAtivo): ?>
                                        <input type="checkbox" name="ativo" value="1"
                                               <?= !empty($prod['ativo']) ? 'checked' : '' ?> title="Ativo">
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-sm btn-primary">Salvar</button>
                                </form>
                            </td>
                            <td><?= (int) $prod['estoque'] ?></td>
                            <?php if ($temColunaAtivo): ?>
                                <td><?= !empty($prod['ativo']) ? 'Sim' : 'Não' ?></td>
                            <?php endif; ?>
                            <td class="d-flex gap-1">
                                <?php if ($temColunaAtivo && !empty($prod['ativo'])): ?>
                                    <form method="post">
                                        <input type="hidden" name="acao" value="desativar">
                                        <input type="hidden" name="id" value="<?= (int) $prod['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">Desativar</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" onsubmit="return confirm('Excluir este produto?');">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= (int) $prod['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pedidos com itens e total -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Pedidos Realizados</h2>
            <?php if (empty($pedidos)): ?>
                <p class="text-muted mb-0">Nenhum pedido registrado ainda.</p>
            <?php else: ?>
                <?php foreach ($pedidos as $ped): ?>
                    <div class="border rounded p-3 mb-3 bg-white">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                            <div>
                                <strong>Pedido #<?= (int) $ped['id'] ?></strong> —
                                <?= htmlspecialchars((string) $ped['cliente_nome'], ENT_QUOTES, 'UTF-8') ?>
                                <small class="text-muted">(<?= htmlspecialchars((string) ($ped['forma_pagamento'] ?? 'PIX'), ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string) $ped['status'], ENT_QUOTES, 'UTF-8') ?>)</small>
                            </div>
                            <strong>Total: R$ <?= number_format((float) $ped['total'], 2, ',', '.') ?></strong>
                        </div>
                        <ul class="mb-0 small">
                            <?php foreach ($ped['itens'] as $item): ?>
                                <li>
                                    <?= (int) $item['quantidade'] ?>x
                                    <?= htmlspecialchars((string) ($item['produto_nome'] ?? 'Item'), ENT_QUOTES, 'UTF-8') ?>
                                    — R$ <?= number_format((float) $item['preco_unitario'], 2, ',', '.') ?> cada
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>