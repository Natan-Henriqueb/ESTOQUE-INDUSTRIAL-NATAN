<?php

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha o e-mail e a senha.';
    } else {

        try {

            $sql = "SELECT id, nome, email, senha, ativo
                    FROM usuarios
                    WHERE email = :email
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':email' => $email
            ]);

            $usuario = $stmt->fetch();

            if (!$usuario) {

                $erro = 'E-mail ou senha incorretos.';

            } elseif (!$usuario['ativo']) {

                $erro = 'Usuário inativo.';

            } elseif ($senha !== $usuario['senha']) {

                $erro = 'E-mail ou senha incorretos.';

            } else {

                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];

                header('Location: index.php');
                exit;
            }

        } catch (PDOException $e) {

            $erro = 'Erro ao realizar o login: ' . $e->getMessage();

        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Controle de Estoque Industrial</title>

    <link rel="stylesheet" href="estilo.css">
</head>

<body>

    <main>

        <h1>Controle de Estoque Industrial</h1>

        <h2>Login</h2>

        <?php if ($erro !== ''): ?>

            <div class="erro">
                <?= htmlspecialchars($erro) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <label for="email">E-mail:</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Digite seu e-mail"
                required
            >

            <label for="senha">Senha:</label>

            <input
                type="password"
                id="senha"
                name="senha"
                placeholder="Digite sua senha"
                required
            >

            <button type="submit">
                Entrar
            </button>

        </form>

    </main>

</body>

</html>