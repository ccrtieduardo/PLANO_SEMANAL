<?php
require_once 'config.php';

// Se já estiver logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard_planosemanal.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email && $senha) {
        $stmt = $pdo->prepare("SELECT id, nome, email, senha, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_role'] = $user['role'];

            // Redirecionamento conforme o perfil
            switch ($user['role']) {
                case 'admin':
                    header('Location: admin.php');
                    break;
                case 'professor':
                    header('Location: professor.php');
                    break;
                case 'coordenador':
                    header('Location: coordenador.php');
                    break;
                default:
                    header('Location: dashboard_planosemanal.php');
            }
            exit;
        } else {
            $error = 'E-mail ou senha inválidos.';
        }
    } else {
        $error = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Planos Semanais</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>

            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>

            <button type="submit">Entrar</button>
        </form>
        <a href="dashboard_planosemanal.php">← Voltar ao painel de planos</a>
    </div>
</body>
</html>