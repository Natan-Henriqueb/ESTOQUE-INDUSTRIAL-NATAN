<?php

require_once 'config.php';

verificarLogin();

$mensagem = '';
$erro = '';

/* =========================================
   EXCLUIR PRODUTO
   ========================================= */

if (isset($_GET['excluir'])) {

    $id = (int) $_GET['excluir'];

    try {

        $stmt = $pdo->prepare("
            DELETE FROM produtos
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $mensagem = 'Produto excluído com sucesso.';

    } catch (PDOException $e) {

        $erro = 'Não foi possível excluir este produto. Ele pode possuir movimentações registradas.';

    }
}


/* =========================================
   CADASTRAR / EDITAR PRODUTO
   ========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int) ($_POST['id'] ?? 0);

    $codigo = trim($_POST['codigo'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
    $fabricante = trim($_POST['fabricante'] ?? '');
    $unidade_medida = trim($_POST['unidade_medida'] ?? '');
    $custo = $_POST['custo'] ?? '';
    $estoque_atual = $_POST['estoque_atual'] ?? '';
    $estoque_minimo = $_POST['estoque_minimo'] ?? '';

    if (
        $codigo === '' ||
        $nome === '' ||
        $categoria_id <= 0 ||
        $unidade_medida === '' ||
        $custo === '' ||
        $estoque_atual === '' ||
        $estoque_minimo === ''
    ) {

        $erro = 'Preencha todos os campos obrigatórios.';

    } elseif (!is_numeric($custo) || $custo < 0) {

        $erro = 'Informe um custo válido.';

    } elseif (!is_numeric($estoque_atual) || $estoque_atual < 0) {

        $erro = 'Informe um estoque atual válido.';

    } elseif (!is_numeric($estoque_minimo) || $estoque_minimo < 0) {

        $erro = 'Informe um estoque mínimo válido.';

    } else {

        try {

            if ($id > 0) {

                $sql = "
                    UPDATE produtos
                    SET
                        codigo = :codigo,
                        nome = :nome,
                        categoria_id = :categoria_id,
                        fabricante = :fabricante,
                        unidade_medida = :unidade_medida,
                        custo = :custo,
                        estoque_atual = :estoque_atual,
                        estoque_minimo = :estoque_minimo
                    WHERE id = :id
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':codigo' => $codigo,
                    ':nome' => $nome,
                    ':categoria_id' => $categoria_id,
                    ':fabricante' => $fabricante,
                    ':unidade_medida' => $unidade_medida,
                    ':custo' => $custo,
                    ':estoque_atual' => $estoque_atual,
                    ':estoque_minimo' => $estoque_minimo,
                    ':id' => $id
                ]);

                $mensagem = 'Produto atualizado com sucesso.';

            } else {

                $sql = "
                    INSERT INTO produtos
                    (
                        codigo,
                        nome,
                        categoria_id,
                        fabricante,
                        unidade_medida,
                        custo,
                        estoque_atual,
                        estoque_minimo,
                        ativo
                    )
                    VALUES
                    (
                        :codigo,
                        :nome,
                        :categoria_id,
                        :fabricante,
                        :unidade_medida,
                        :custo,
                        :estoque_atual,
                        :estoque_minimo,
                        1
                    )
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':codigo' => $codigo,
                    ':nome' => $nome,
                    ':categoria_id' => $categoria_id,
                    ':fabricante' => $fabricante,
                    ':unidade_medida' => $unidade_medida,
                    ':custo' => $custo,
                    ':estoque_atual' => $estoque_atual,
                    ':estoque_minimo' => $estoque_minimo
                ]);

                $mensagem = 'Produto cadastrado com sucesso.';
            }

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {
                $erro = 'O código do produto já está cadastrado.';
            } else {
                $erro = 'Erro ao salvar o produto: ' . $e->getMessage();
            }
        }
    }
}


/* =========================================
   PRODUTO PARA EDIÇÃO
   ========================================= */

$produtoEditar = null;

if (isset($_GET['editar'])) {

    $id = (int) $_GET['editar'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM produtos
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $produtoEditar = $stmt->fetch();
}


/* =========================================
   CATEGORIAS
   ========================================= */

$stmt = $pdo->query("
    SELECT *
    FROM categorias
    ORDER BY nome
");

$categorias = $stmt->fetchAll();


/* =========================================
   PESQUISA
   ========================================= */

$busca = trim($_GET['busca'] ?? '');

if ($busca !== '') {

    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.nome AS categoria_nome
        FROM produtos p
        INNER JOIN categorias c
            ON c.id = p.categoria_id
        WHERE
            p.codigo LIKE :busca
            OR p.nome LIKE :busca
            OR c.nome LIKE :busca
        ORDER BY p.nome
    ");

    $stmt->execute([
        ':busca' => '%' . $busca . '%'
    ]);

} else {

    $stmt = $pdo->query("
        SELECT
            p.*,
            c.nome AS categoria_nome
        FROM produtos p
        INNER JOIN categorias c
            ON c.id = p.categoria_id
        ORDER BY p.nome
    ");
}

$produtos = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Produtos - Controle de Estoque Industrial</title>

    <link rel="stylesheet" href="estilo.css">

</head>

<body>

<header>

    <h1>Cadastro de Produtos</h1>

    <p>
        Usuário:
        <strong>
            <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
        </strong>
    </p>

</header>

<main>

    <p>
        <a href="index.php">← Voltar para o menu principal</a>
    </p>


    <?php if ($mensagem !== ''): ?>

        <div class="sucesso">
            <?= htmlspecialchars($mensagem) ?>
        </div>

    <?php endif; ?>


    <?php if ($erro !== ''): ?>

        <div class="erro">
            <?= htmlspecialchars($erro) ?>
        </div>

    <?php endif; ?>


    <h2>
        <?= $produtoEditar ? 'Editar Produto' : 'Cadastrar Produto' ?>
    </h2>


    <form method="POST">

        <?php if ($produtoEditar): ?>

            <input
                type="hidden"
                name="id"
                value="<?= $produtoEditar['id'] ?>"
            >

        <?php endif; ?>


        <label for="codigo">
            Código:
        </label>

        <input
            type="text"
            id="codigo"
            name="codigo"
            required
            value="<?= htmlspecialchars($produtoEditar['codigo'] ?? '') ?>"
            placeholder="Ex.: MAT-004"
        >


        <label for="nome">
            Nome do material:
        </label>

        <input
            type="text"
            id="nome"
            name="nome"
            required
            value="<?= htmlspecialchars($produtoEditar['nome'] ?? '') ?>"
            placeholder="Ex.: Chapa de aço"
        >


        <label for="categoria_id">
            Categoria:
        </label>

        <select
            id="categoria_id"
            name="categoria_id"
            required
        >

            <option value="">
                Selecione uma categoria
            </option>

            <?php foreach ($categorias as $categoria): ?>

                <option
                    value="<?= $categoria['id'] ?>"
                    <?= isset($produtoEditar['categoria_id']) &&
                        $produtoEditar['categoria_id'] == $categoria['id']
                        ? 'selected'
                        : '' ?>
                >

                    <?= htmlspecialchars($categoria['nome']) ?>

                </option>

            <?php endforeach; ?>

        </select>


        <label for="fabricante">
            Fabricante:
        </label>

        <input
            type="text"
            id="fabricante"
            name="fabricante"
            value="<?= htmlspecialchars($produtoEditar['fabricante'] ?? '') ?>"
            placeholder="Ex.: Gerdau"
        >


        <label for="unidade_medida">
            Unidade de medida:
        </label>

        <select
            id="unidade_medida"
            name="unidade_medida"
            required
        >

            <option value="">
                Selecione
            </option>

            <?php

            $unidades = [
                'UN',
                'KG',
                'M',
                'L',
                'CX'
            ];

            foreach ($unidades as $unidade):

            ?>

                <option
                    value="<?= $unidade ?>"
                    <?= isset($produtoEditar['unidade_medida']) &&
                        $produtoEditar['unidade_medida'] === $unidade
                        ? 'selected'
                        : '' ?>
                >

                    <?= $unidade ?>

                </option>

            <?php endforeach; ?>

        </select>


        <label for="custo">
            Custo:
        </label>

        <input
            type="number"
            id="custo"
            name="custo"
            min="0"
            step="0.01"
            required
            value="<?= htmlspecialchars($produtoEditar['custo'] ?? '') ?>"
        >


        <label for="estoque_atual">
            Estoque atual:
        </label>

        <input
            type="number"
            id="estoque_atual"
            name="estoque_atual"
            min="0"
            step="0.01"
            required
            value="<?= htmlspecialchars($produtoEditar['estoque_atual'] ?? '') ?>"
        >


        <label for="estoque_minimo">
            Estoque mínimo:
        </label>

        <input
            type="number"
            id="estoque_minimo"
            name="estoque_minimo"
            min="0"
            step="0.01"
            required
            value="<?= htmlspecialchars($produtoEditar['estoque_minimo'] ?? '') ?>"
        >


        <button type="submit">

            <?= $produtoEditar
                ? 'Salvar Alterações'
                : 'Cadastrar Produto' ?>

        </button>

        <?php if ($produtoEditar): ?>

            <a href="produtos.php">
                Cancelar edição
            </a>

        <?php endif; ?>

    </form>


    <hr>


    <h2>Pesquisar Produtos</h2>

    <form method="GET">

        <input
            type="text"
            name="busca"
            placeholder="Código, nome ou categoria"
            value="<?= htmlspecialchars($busca) ?>"
        >

        <button type="submit">
            Pesquisar
        </button>

        <a href="produtos.php">
            Limpar
        </a>

    </form>


    <h2>Produtos Cadastrados</h2>

    <table>

        <thead>

            <tr>

                <th>Código</th>
                <th>Produto</th>
                <th>Categoria</th>
                <th>Fabricante</th>
                <th>Unidade</th>
                <th>Custo</th>
                <th>Estoque</th>
                <th>Mínimo</th>
                <th>Situação</th>
                <th>Ações</th>

            </tr>

        </thead>

        <tbody>

            <?php if (count($produtos) > 0): ?>

                <?php foreach ($produtos as $produto): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($produto['codigo']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($produto['nome']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($produto['categoria_nome']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($produto['fabricante'] ?? '') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($produto['unidade_medida']) ?>
                        </td>

                        <td>
                            R$ <?= number_format(
                                $produto['custo'],
                                2,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            <?= number_format(
                                $produto['estoque_atual'],
                                2,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            <?= number_format(
                                $produto['estoque_minimo'],
                                2,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>

                            <?php if (
                                $produto['estoque_atual']
                                <=
                                $produto['estoque_minimo']
                            ): ?>

                                <strong>
                                    ESTOQUE BAIXO
                                </strong>

                            <?php else: ?>

                                Normal

                            <?php endif; ?>

                        </td>

                        <td>

                            <a
                                href="produtos.php?editar=<?= $produto['id'] ?>"
                            >
                                Editar
                            </a>

                            |

                            <a
                                href="produtos.php?excluir=<?= $produto['id'] ?>"
                                onclick="return confirm('Tem certeza que deseja excluir este produto?');"
                            >
                                Excluir
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="10">
                        Nenhum produto encontrado.
                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</main>

</body>

</html>