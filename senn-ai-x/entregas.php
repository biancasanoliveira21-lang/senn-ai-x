
<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexao.php';

function h($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cadastrar') {
        $destinatario = trim($_POST['destinatario'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $janela = trim($_POST['janela_entrega'] ?? '');
        $peso = filter_var($_POST['peso'] ?? '', FILTER_VALIDATE_FLOAT);
        $volume = filter_var($_POST['volume'] ?? '', FILTER_VALIDATE_FLOAT);
        $prioridade = $_POST['prioridade'] ?? '';

        if (
            $destinatario === '' || strlen($destinatario) > 100 ||
            $endereco === '' || strlen($endereco) > 255 ||
            $peso === false || $peso < 0 ||
            $volume === false || $volume < 0 ||
            !in_array($prioridade, ['Baixa', 'Média', 'Alta'], true)
        ) {
            $mensagem = 'Confira os campos e informe valores válidos.';
        } else {
            $data = null;

            if ($janela !== '') {
                $dataObj = DateTime::createFromFormat('Y-m-d\TH:i', $janela);

                if (!$dataObj || $dataObj->format('Y-m-d\TH:i') !== $janela) {
                    $mensagem = 'Informe uma data de entrega válida.';
                } else {
                    $data = $dataObj->format('Y-m-d H:i:s');
                }
            }

            if ($mensagem === '') {
                $stmt = $pdo->prepare(
                    'INSERT INTO entregas
                    (destinatario, endereco, janela_entrega, peso, volume, prioridade)
                    VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $destinatario,
                    $endereco,
                    $data,
                    $peso,
                    $volume,
                    $prioridade
                ]);

                header('Location: entregas.php?sucesso=1');
                exit;
            }
        }
    }

    if ($acao === 'status') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';

        if (
            $id &&
            in_array($status, ['Pendente', 'A caminho', 'Entregue', 'Falha'], true)
        ) {
            $stmt = $pdo->prepare(
                'UPDATE entregas SET status = ? WHERE id = ?'
            );
            $stmt->execute([$status, $id]);

            header('Location: entregas.php?atualizado=1');
            exit;
        }

        $mensagem = 'Não foi possível atualizar a entrega.';
    }

    if ($acao === 'excluir') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM entregas WHERE id = ?');
            $stmt->execute([$id]);

            header('Location: entregas.php?excluido=1');
            exit;
        }

        $mensagem = 'Código inválido.';
    }
}

$entregas = $pdo->query(
    'SELECT * FROM entregas ORDER BY id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entregas | SENN AI-X</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topo">
    <a class="logo" href="index.php">SENN <span>AI-X</span></a>
    <a class="sair" href="login.php?sair=1">Sair</a>
</header>

<div class="layout">
    <aside class="menu">
        <h3>MENU</h3>
        <a href="index.php">Painel</a>
        <a class="ativo" href="entregas.php">Entregas</a>
        <a href="veiculos.php">Veículos</a>
        <a href="motoristas.php">Motoristas</a>
        <a href="rotas.php">Rotas</a>
    </aside>

    <main class="conteudo">
        <h1>Entregas</h1>
        <p class="subtitulo">Cadastre e acompanhe os pedidos.</p>

        <?php if ($mensagem !== ''): ?>
            <p class="mensagem erro"><?= h($mensagem) ?></p>
        <?php endif; ?>

        <?php if (isset($_GET['sucesso'])): ?>
            <p class="mensagem">Entrega cadastrada com sucesso!</p>
        <?php endif; ?>

        <?php if (isset($_GET['atualizado'])): ?>
            <p class="mensagem">Status atualizado!</p>
        <?php endif; ?>

        <?php if (isset($_GET['excluido'])): ?>
            <p class="mensagem">Entrega excluída!</p>
        <?php endif; ?>

        <section class="painel">
            <h2>Nova entrega</h2>

            <form method="POST" class="formulario">
                <input type="hidden" name="acao" value="cadastrar">

                <label for="destinatario">Destinatário</label>
                <input id="destinatario" name="destinatario"
                       maxlength="100" required>

                <label for="endereco">Endereço de entrega</label>
                <input id="endereco" name="endereco"
                       maxlength="255" required>

                <label for="janela_entrega">Data e horário previstos</label>
                <input type="datetime-local" id="janela_entrega"
                       name="janela_entrega">

                <div class="campos-duplos">
                    <div>
                        <label for="peso">Peso (kg)</label>
                        <input type="number" id="peso" name="peso"
                               min="0" step="0.01" required>
                    </div>
                    <div>
                        <label for="volume">Volume (m³)</label>
                        <input type="number" id="volume" name="volume"
                               min="0" step="0.01" required>
                    </div>
                </div>

                <label for="prioridade">Prioridade</label>
                <select id="prioridade" name="prioridade" required>
                    <option value="Baixa">Baixa</option>
                    <option value="Média" selected>Média</option>
                    <option value="Alta">Alta</option>
                </select>

                <button type="submit">Cadastrar entrega</button>
            </form>
        </section>

        <section class="painel">
            <h2>Entregas cadastradas</h2>

            <div class="tabela-responsiva">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Destinatário</th>
                            <th>Endereço</th>
                            <th>Prioridade</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($entregas as $e): ?>
                        <tr>
                            <td><?= h($e['id']) ?></td>
                            <td><?= h($e['destinatario']) ?></td>
                            <td><?= h($e['endereco']) ?></td>
                            <td><?= h($e['prioridade']) ?></td>
                            <td>
                                <form method="POST" class="form-status">
                                    <input type="hidden" name="acao" value="status">
                                    <input type="hidden" name="id"
                                           value="<?= h($e['id']) ?>">
                                    <select name="status" aria-label="Status da entrega">
                                        <?php foreach (
                                            ['Pendente', 'A caminho', 'Entregue', 'Falha']
                                            as $s
                                        ): ?>
                                            <option value="<?= h($s) ?>"
                                                <?= $e['status'] === $s ? 'selected' : '' ?>>
                                                <?= h($s) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="botao-pequeno">
                                        Salvar
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Excluir esta entrega?')">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id"
                                           value="<?= h($e['id']) ?>">
                                    <button class="botao-excluir" type="submit">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$entregas): ?>
                        <tr>
                            <td colspan="6">Nenhuma entrega cadastrada.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
