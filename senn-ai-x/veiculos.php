
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require 'conexao.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cadastrar') {
        $placa = strtoupper(trim($_POST['placa'] ?? ''));
        $tipo = trim($_POST['tipo'] ?? '');
        $kg = filter_var($_POST['capacidade_kg'] ?? '', FILTER_VALIDATE_FLOAT);
        $volume = filter_var($_POST['capacidade_volume'] ?? '', FILTER_VALIDATE_FLOAT);
        $autonomia = filter_var($_POST['autonomia'] ?? '', FILTER_VALIDATE_FLOAT);
        $status = $_POST['status'] ?? '';
        $manutencao = trim($_POST['proxima_manutencao'] ?? '');

        if (
            !preg_match('/^[A-Z0-9-]{5,10}$/', $placa) ||
            $tipo === '' || strlen($tipo) > 50 ||
            $kg === false || $kg <= 0 ||
            $volume === false || $volume <= 0 ||
            $autonomia === false || $autonomia < 0 ||
            !in_array($status, ['Disponível', 'Em rota', 'Em manutenção'], true)
        ) {
            $mensagem = 'Confira os dados informados.';
        } else {
            $data = null;

            if ($manutencao !== '') {
                $dt = DateTime::createFromFormat('Y-m-d', $manutencao);
                if (!$dt || $dt->format('Y-m-d') !== $manutencao) {
                    $mensagem = 'Data de manutenção inválida.';
                } else {
                    $data = $manutencao;
                }
            }

            if ($mensagem === '') {
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO veiculos
                        (placa,tipo,capacidade_kg,capacidade_volume,autonomia,status,proxima_manutencao)
                        VALUES (?,?,?,?,?,?,?)'
                    );
                    $stmt->execute([$placa,$tipo,$kg,$volume,$autonomia,$status,$data]);
                    header('Location: veiculos.php?sucesso=1');
                    exit;
                } catch (PDOException $e) {
                    $mensagem = 'Erro ao cadastrar. Confira se a placa já existe.';
                }
            }
        }
    }

    if ($acao === 'excluir') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM veiculos WHERE id=?');
            $stmt->execute([$id]);
            header('Location: veiculos.php?excluido=1');
            exit;
        }
    }
}

$veiculos = $pdo->query('SELECT * FROM veiculos ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Veículos | SENN AI-X</title>
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
<a href="entregas.php">Entregas</a>
<a class="ativo" href="veiculos.php">Veículos</a>
<a href="motoristas.php">Motoristas</a>
<a href="rotas.php">Rotas</a>
</aside>
<main class="conteudo">
<h1>Veículos da frota</h1>
<p class="subtitulo">Gerencie os veículos cadastrados.</p>

<?php if ($mensagem): ?><p class="mensagem erro"><?=h($mensagem)?></p><?php endif; ?>
<?php if (isset($_GET['sucesso'])): ?><p class="mensagem">Veículo cadastrado!</p><?php endif; ?>
<?php if (isset($_GET['excluido'])): ?><p class="mensagem">Veículo excluído!</p><?php endif; ?>

<section class="painel">
<h2>Novo veículo</h2>
<form method="POST" class="formulario">
<input type="hidden" name="acao" value="cadastrar">
<label for="placa">Placa</label>
<input id="placa" name="placa" maxlength="10" required>
<label for="tipo">Tipo de veículo</label>
<input id="tipo" name="tipo" maxlength="50" placeholder="Ex.: Caminhão" required>

<div class="campos-duplos">
<div><label for="capacidade_kg">Capacidade (kg)</label>
<input type="number" id="capacidade_kg" name="capacidade_kg" min="0.01" step="0.01" required></div>
<div><label for="capacidade_volume">Volume (m³)</label>
<input type="number" id="capacidade_volume" name="capacidade_volume" min="0.01" step="0.01" required></div>
</div>

<label for="autonomia">Autonomia (km)</label>
<input type="number" id="autonomia" name="autonomia" min="0" step="0.01" required>

<label for="status">Status</label>
<select id="status" name="status">
<option>Disponível</option><option>Em rota</option><option>Em manutenção</option>
</select>

<label for="proxima_manutencao">Próxima manutenção</label>
<input type="date" id="proxima_manutencao" name="proxima_manutencao">
<button type="submit">Cadastrar veículo</button>
</form>
</section>

<section class="painel">
<h2>Veículos cadastrados</h2>
<div class="tabela-responsiva">
<table>
<thead><tr><th>Placa</th><th>Tipo</th><th>Capacidade kg</th><th>Volume m³</th><th>Autonomia</th><th>Status</th><th>Manutenção</th><th>Ação</th></tr></thead>
<tbody>
<?php foreach ($veiculos as $v): ?>
<tr>
<td><?=h($v['placa'])?></td><td><?=h($v['tipo'])?></td>
<td><?=h($v['capacidade_kg'])?></td><td><?=h($v['capacidade_volume'])?></td>
<td><?=h($v['autonomia'])?> km</td><td><?=h($v['status'])?></td>
<td><?=h($v['proxima_manutencao'] ?: 'Não informada')?></td>
<td><form method="POST" onsubmit="return confirm('Excluir este veículo?')">
<input type="hidden" name="acao" value="excluir">
<input type="hidden" name="id" value="<?=h($v['id'])?>">
<button class="botao-excluir" type="submit">Excluir</button>
</form></td>
</tr>
<?php endforeach; ?>
<?php if (!$veiculos): ?><tr><td colspan="8">Nenhum veículo cadastrado.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
</section>
</main>
</div>
</body>
</html>
