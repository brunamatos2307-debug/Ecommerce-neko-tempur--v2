<?php
session_start();
require_once __DIR__ . '/conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nome, email, senha_hash, tipo FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id'    => (int) $usuario['id'],
                'nome'  => $usuario['nome'],
                'email' => $usuario['email'],
                'tipo'  => $usuario['tipo'],
            ];

            $voltarPara = $_SESSION['voltar_para'] ?? null;
            unset($_SESSION['voltar_para']);

            if ($voltarPara) {
                header('Location: ' . $voltarPara);
                exit;
            }

            if ($usuario['tipo'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.html');
            }
            exit;
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
</head>
<body>
  <h2>Login</h2>

  <?php if ($erro !== ''): ?>
    <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
  <?php endif; ?>

  <form method="POST">
    <p>
      <label>E-mail:</label><br>
      <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </p>
    <p>
      <label>Senha:</label><br>
      <input type="password" name="senha" required>
    </p>
    <button type="submit">Entrar</button>
  </form>

  <p><a href="cadastro.php">Não tem conta? Cadastrar</a> | <a href="index.html">Voltar ao site</a></p>
</body>
</html>