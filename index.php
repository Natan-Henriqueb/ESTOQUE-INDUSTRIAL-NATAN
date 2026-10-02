<?php

require_once 'config.php';

verificarLogin();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Controle de Estoque Industrial</title>

    <link rel="stylesheet" href="estilo.css">

</head>

<body>

<header>

    <h1>Controle de Estoque Industrial</h1>

    <p>
        Usuário conectado:
        <strong>
            <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
        </strong>
    </p>

</header>

<main>

    <h2>Menu Principal</h2>

    <p class="mensagem-inicial">
        Bem-vindo ao Sistema de Controle de Estoque Industrial.
    </p>

    <div class="menu">

        <a href="produtos.php" class="botao">
            Cadastro de Produtos
        </a>

        <a href="estoque.php" class="botao">
            Gestão de Estoque
        </a>

        <a href="index.php?logout=1" class="botao sair">
            Sair
        </a>

    </div>

</main>

</body>

</html>