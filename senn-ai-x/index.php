
<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexao.php';

$totalEntregas = (int) $pdo->query(
    'SELECT COUNT(*) FROM entregas'
)->fetchColumn();

$pendentes = (int) $pdo->query(
    "SELECT COUNT(*) FROM entregas WHERE status = 'Pendente'"
)->fetchColumn();

$emRota = (int) $pdo->query(
    "SELECT COUNT(*) FROM entregas WHERE status = 'A caminho'"
)->fetchColumn();

$entregues = (int) $pdo->query(
    "SELECT COUNT(*) FROM entregas WHERE status = 'Entregue'"
)->fetchColumn();

$totalVeiculos = (int) $pdo->query(
    'SELECT COUNT(*) FROM veiculos'
)->fetchColumn();

$totalMotoristas = (int) $pdo->query(
    'SELECT COUNT(*) FROM motoristas'
)->fetchColumn();

$ultimas = $pdo->query(
    'SELECT * FROM entregas ORDER BY id DESC LIMIT 5'
)->fetchAll();

function h($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel | SENN AI-X</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topo">
    <a class="logo" href="index.php">SENN <span>AI-X</span></a>
    <div>
        Olá, <?= h($_SESSION['usuario_nome']) ?>
        <a class="sair" href="login.php?sair=1">Sair</a>
    </div>
</header>

<div class="layout">
    <aside class="menu">
        <h3>MENU</h3>
        <a class="ativo" href="index.php">Painel</a>
        <a href="entregas.php">Entregas</a>
        <a href="veiculos.php">Veículos</a>
        <a href="motoristas.php">Motoristas</a>
        <a href="rotas.php">Rotas</a>
    </aside>

    <main class="conteudo">
        <h1>Painel de controle</h1>
        <p class="subtitulo">
            Acompanhe os principais dados da operação logística.
        </p>

        <section class="indicadores">
            <article class="indicador">
                <p>Total de entregas</p>
                <strong><?= $totalEntregas ?></strong>
            </article>
            <article class="indicador">
                <p>Pendentes</p>
                <strong><?= $pendentes ?></strong>
            </article>
            <article class="indicador">
                <p>Em rota</p>
                <strong><?= $emRota ?></strong>
            </article>
            <article class="indicador">
                <p>Entregues</p>
                <strong><?= $entregues ?></strong>
            </article>
            <article class="indicador">
                <p>Veículos</p>
                <strong><?= $totalVeiculos ?></strong>
            </article>
            <article class="indicador">
                <p>Motoristas</p>
                <strong><?= $totalMotoristas ?></strong>
            </article>
        </section>

        <section class="painel">
            <h2>Entregas recentes</h2>

            <div class="tabela-responsiva">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Destinatário</th>
                            <th>Endereço</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ultimas as $entrega): ?>
                        <tr>
                            <td><?= h($entrega['id']) ?></td>
                            <td><?= h($entrega['destinatario']) ?></td>
                            <td><?= h($entrega['endereco']) ?></td>
                            <td><?= h($entrega['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$ultimas): ?>
                        <tr>
                            <td colspan="4">Nenhuma entrega cadastrada.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a class="botao-link" href="entregas.php">Gerenciar entregas</a>
        </section>
    </main>
</div>
</body>
</html>
