<?php

require_once 'config.php';

verificarLogin();

$mensagem = '';
$erro = '';
$alertaEstoque = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $produto_id = (int) ($_POST['produto_id'] ?? 0);
    $tipo = strtoupper(trim($_POST['tipo'] ?? ''));
    $quantidade = $_POST['quantidade'] ?? '';
    $data = trim($_POST['data'] ?? '');

 

    if (
        $produto_id <= 0 ||
        $tipo === '' ||
        $quantidade === '' ||
        $data === ''
    ) {

        $erro = 'Preencha todos os campos da movimentação.';

    } elseif (!in_array($tipo, ['ENTRADA', 'SAIDA'])) {

        $erro = 'Tipo de movimentação inválido.';

    } elseif (!is_numeric($quantidade) || $quantidade <= 0) {

        $erro = 'Informe uma quantidade válida.';

    } else {

        try {

           

            $stmt = $pdo->prepare("
                SELECT *
                FROM produtos
                WHERE id = :id
                AND ativo = 1
                LIMIT 1
            ");

            $stmt->execute([
                ':id' => $produto_id
            ]);

            $produto = $stmt->fetch();

            if (!$produto) {

                $erro = 'Produto não encontrado.';

            } else {

                $quantidade = (float) $quantidade;

                $estoqueAnterior = (float) $produto['estoque_atual'];

                

                if ($tipo === 'ENTRADA') {

                    $estoquePosterior =
                        $estoqueAnterior + $quantidade;

                } else {

                    $estoquePosterior =
                        $estoqueAnterior - $quantidade;

                }


                

                if ($estoquePosterior < 0) {

                    $erro =
                        'Não é possível realizar a saída. ' .
                        'O estoque não pode ficar negativo.';

                } else {

                    

                    $pdo->beginTransaction();

                    

                    $stmt = $pdo->prepare("
                        UPDATE produtos
                        SET estoque_atual = :estoque
                        WHERE id = :id
                    ");

                    $stmt->execute([
                        ':estoque' => $estoquePosterior,
                        ':id' => $produto_id
                    ]);


                    

                    $tipo_id = ($tipo === 'ENTRADA') ? 1 : 2;

                    $stmt = $pdo->prepare("
                        INSERT INTO movimentacoes
                        (
                            produto_id,
                            tipo_id,
                            usuario_id,
                            quantidade,
                            estoque_anterior,
                            estoque_posterior,
                            data_movimento
                        )
                        VALUES
                        (
                            :produto_id,
                            :tipo_id,
                            :usuario_id,
                            :quantidade,
                            :estoque_anterior,
                            :estoque_posterior,
                            :data_movimento
                        )
                    ");

                    $stmt->execute([
                        ':produto_id' => $produto_id,
                        ':tipo_id' => $tipo_id,
                        ':usuario_id' => $_SESSION['usuario_id'],
                        ':quantidade' => $quantidade,
                        ':estoque_anterior' => $estoqueAnterior,
                        ':estoque_posterior' => $estoquePosterior,
                        ':data_movimento' => $data
                    ]);


                    

                    $pdo->commit();


                    $mensagem =
                        'Movimentação registrada com sucesso.';


                    

                    if (
                        $estoquePosterior
                        <=
                        (float) $produto['estoque_minimo']
                    ) {

                        $alertaEstoque =
                            'ATENÇÃO: o estoque de "' .
                            $produto['nome'] .
                            '" está abaixo ou igual ao estoque mínimo.';
                    }
                }
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erro =
                'Erro ao registrar a movimentação: ' .
                $e->getMessage();
        }
    }
}




$stmt = $pdo->query("
    SELECT
        p.*,
        c.nome AS categoria_nome
    FROM produtos p
    INNER JOIN categorias c
        ON c.id = p.categoria_id
    WHERE p.ativo = 1
    ORDER BY p.nome
");

$produtos = $stmt->fetchAll();




$stmt = $pdo->query("
    SELECT
        m.id,
        m.quantidade,
        m.estoque_anterior,
        m.estoque_posterior,
        m.data_movimento,

        p.nome AS produto_nome,
        p.codigo AS produto_codigo,

        t.nome AS tipo_nome,

        u.nome AS usuario_nome

    FROM movimentacoes m

    INNER JOIN produtos p
        ON p.id = m.produto_id

    INNER JOIN tipos_movimentacao t
        ON t.id = m.tipo_id

    INNER JOIN usuarios u
        ON u.id = m.usuario_id

    ORDER BY m.data_movimento DESC, m.id DESC
");

$movimentacoes = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Gestão de Estoque - Controle Industrial</title>

    <link
        rel="stylesheet"
        href="estilo.css"
    >

</head>

<body>

<header>

    <h1>Gestão de Estoque</h1>

    <p>

        Usuário:

        <strong>
            <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
        </strong>

    </p>

</header>


<main>

    <p>

        <a href="index.php">
            ← Voltar para o menu principal
        </a>

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


    <?php if ($alertaEstoque !== ''): ?>

        <div class="alerta">

            <?= htmlspecialchars($alertaEstoque) ?>

        </div>

    <?php endif; ?>




    <h2>Registrar Movimentação</h2>

    <form method="POST">

        <label for="produto_id">
            Produto:
        </label>

        <select
            name="produto_id"
            id="produto_id"
            required
        >

            <option value="">
                Selecione um produto
            </option>

            <?php foreach ($produtos as $produto): ?>

                <option value="<?= $produto['id'] ?>">

                    <?= htmlspecialchars($produto['codigo']) ?>
                    -
                    <?= htmlspecialchars($produto['nome']) ?>

                    |
                    Estoque:
                    <?= number_format(
                        $produto['estoque_atual'],
                        2,
                        ',',
                        '.'
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>


        <label>
            Tipo de movimentação:
        </label>

        <div>

            <label>

                <input
                    type="radio"
                    name="tipo"
                    value="ENTRADA"
                    required
                >

                Entrada

            </label>


            <label>

                <input
                    type="radio"
                    name="tipo"
                    value="SAIDA"
                >

                Saída

            </label>

        </div>


        <label for="quantidade">
            Quantidade:
        </label>

        <input
            type="number"
            id="quantidade"
            name="quantidade"
            min="0.01"
            step="0.01"
            required
        >


        <label for="data">
            Data da movimentação:
        </label>

        <input
            type="datetime-local"
            id="data"
            name="data"
            value="<?= date('Y-m-d\TH:i') ?>"
            required
        >


        <button type="submit">

            Registrar Movimentação

        </button>

    </form>


    

    <h2>Estoque Atual</h2>

    <table>

        <thead>

            <tr>

                <th>Código</th>

                <th>Produto</th>

                <th>Categoria</th>

                <th>Unidade</th>

                <th>Estoque Atual</th>

                <th>Estoque Mínimo</th>

                <th>Situação</th>

            </tr>

        </thead>


        <tbody>

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
                        <?= htmlspecialchars($produto['unidade_medida']) ?>
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

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>


    
    <h2>Histórico de Movimentações</h2>

    <table>

        <thead>

            <tr>

                <th>ID</th>

                <th>Data</th>

                <th>Produto</th>

                <th>Tipo</th>

                <th>Quantidade</th>

                <th>Estoque Anterior</th>

                <th>Estoque Posterior</th>

                <th>Responsável</th>

            </tr>

        </thead>


        <tbody>

            <?php if (count($movimentacoes) > 0): ?>

                <?php foreach ($movimentacoes as $mov): ?>

                    <tr>

                        <td>
                            <?= $mov['id'] ?>
                        </td>

                        <td>
                            <?= date(
                                'd/m/Y H:i',
                                strtotime($mov['data_movimento'])
                            ) ?>
                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $mov['produto_codigo']
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $mov['produto_nome']
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $mov['tipo_nome']
                            ) ?>

                        </td>

                        <td>

                            <?= number_format(
                                $mov['quantidade'],
                                2,
                                ',',
                                '.'
                            ) ?>

                        </td>

                        <td>

                            <?= number_format(
                                $mov['estoque_anterior'],
                                2,
                                ',',
                                '.'
                            ) ?>

                        </td>

                        <td>

                            <?= number_format(
                                $mov['estoque_posterior'],
                                2,
                                ',',
                                '.'
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $mov['usuario_nome']
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="8">

                        Nenhuma movimentação registrada.

                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</main>

</body>

</html>