<?php
session_start();
require_once __DIR__ . '/conexao.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome === '' || $email === '' || $senha === '') {
        $erro = 'Preencha nome, e-mail e senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha precisa ter pelo menos 6 caracteres.';
    } else {
        $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $check->execute([':email' => $email]);

        if ($check->fetch()) {
            $erro = 'Esse e-mail já está cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha_hash, tipo) VALUES (:nome, :email, :senha_hash, :tipo)'
            );
            $stmt->execute([
                ':nome'       => $nome,
                ':email'      => $email,
                ':senha_hash' => $hash,
                ':tipo'       => 'cliente',
            ]);
            $sucesso = 'Cadastro feito! Agora faça login para finalizar seu pedido.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Cadastro</title>
</head>
<body>
  <h2>Cadastro</h2>

  <?php if ($erro !== ''): ?>
    <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
  <?php endif; ?>

  <?php if ($sucesso !== ''): ?>
    <p style="color: green;"><?= htmlspecialchars($sucesso) ?> <a href="login.php">Ir para o login</a></p>
  <?php endif; ?>

  <form method="POST">
    <p>
      <label>Nome:</label><br>
      <input type="text" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
    </p>
    <p>
      <label>E-mail:</label><br>
      <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </p>
    <p>
      <label>Senha:</label><br>
      <input type="password" name="senha" required>
    </p>
    <button type="submit">Cadastrar</button>
  </form>

  <p><a href="login.php">Já tenho conta (Login)</a> | <a href="index.html">Voltar ao site</a></p>
</body>
</html>