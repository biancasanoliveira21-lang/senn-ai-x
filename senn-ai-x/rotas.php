
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
        $nome = trim($_POST['nome'] ?? '');
        $cnh = strtoupper(trim($_POST['categoria_cnh'] ?? ''));
        $validade = $_POST['validade_cnh'] ?? '';
        $telefone = trim($_POST['telefone'] ?? '');
        $disponibilidade = $_POST['disponibilidade'] ?? '';
        $veiculo = $_POST['veiculo_id'] ?? '';

        $data = DateTime::createFromFormat('Y-m-d', $validade);
        $veiculoId = $veiculo === '' ? null : filter_var($veiculo, FILTER_VALIDATE_INT);

        if (
            $nome === '' || strlen($nome) > 100 ||
            !in_array($cnh, ['A','B','C','D','E','AB','AC','AD','AE'], true) ||
            !$data || $data->format('Y-m-d') !== $validade ||
            $telefone === '' || strlen($telefone) > 20 ||
            !in_array($disponibilidade, ['Disponível','Em rota','Indisponível'], true) ||
            ($veiculo !== '' && !$veiculoId)
        ) {
            $mensagem = 'Confira os dados do motorista.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO motoristas
                (nome,categoria_cnh,validade_cnh,telefone,disponibilidade,veiculo_id)
                VALUES (?,?,?,?,?,?)'
            );

            try {
                $stmt->execute([
                    $nome, $cnh, $validade, $telefone,
                    $disponibilidade, $veiculoId
                ]);
                header('Location: motoristas.php?sucesso=1');
                exit;
            } catch (PDOException $e) {
                $mensagem = 'Não foi possível cadastrar o motorista.';
            }
        }
    }

    if ($acao === 'excluir') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM motoristas WHERE id=?');
            $stmt->execute([$id]);
            header('Location: motoristas.php?excluido=1');
            exit;
        }
    }
}

$veiculos = $pdo->query('SELECT id,placa FROM veiculos ORDER BY placa')->fetchAll();

$motoristas = $pdo->query(
    'SELECT m.*, v.placa
     FROM motoristas m
     LEFT JOIN veiculos v ON v.id=m.veiculo_id
     ORDER BY m.id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Motoristas | SENN AI-X</title>
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
<a href="veiculos.php">Veículos</a>
<a class="ativo" href="motoristas.php">Motoristas</a>
<a href="rotas.php">Rotas</a>
</aside>
<main class="conteudo">
<h1>Motoristas</h1>
<p class="subtitulo">Gerencie os profissionais e suas CNHs.</p>

<?php if ($mensagem): ?><p class="mensagem erro"><?=h($mensagem)?></p><?php endif; ?>
<?php if (isset($_GET['sucesso'])): ?><p class="mensagem">Motorista cadastrado!</p><?php endif; ?>
<?php if (isset($_GET['excluido'])): ?><p class="mensagem">Motorista excluído!</p><?php endif; ?>

<section class="painel">
<h2>Novo motorista</h2>
<form method="POST" class="formulario">
<input type="hidden" name="acao" value="cadastrar">

<label for="nome">Nome completo</label>
<input id="nome" name="nome" maxlength="100" required>

<label for="categoria_cnh">Categoria da CNH</label>
<select id="categoria_cnh" name="categoria_cnh" required>
<option value="">Selecione</option>
<option>A</option><option>B</option><option>C</option>
<option>D</option><option>E</option><option>AB</option>
<option>AC</option><option>AD</option><option>AE</option>
</select>

<label for="validade_cnh">Validade da CNH</label>
<input type="date" id="validade_cnh" name="validade_cnh" required>

<label for="telefone">Telefone</label>
<input id="telefone" name="telefone" maxlength="20" required>

<label for="disponibilidade">Disponibilidade</label>
<select id="disponibilidade" name="disponibilidade">
<option>Disponível</option><option>Em rota</option><option>Indisponível</option>
</select>

<label for="veiculo_id">Veículo vinculado</label>
<select id="veiculo_id" name="veiculo_id">
<option value="">Nenhum veículo</option>
<?php foreach ($veiculos as $v): ?>
<option value="<?=h($v['id'])?>"><?=h($v['placa'])?></option>
<?php endforeach; ?>
</select>

<button type="submit">Cadastrar motorista</button>
</form>
</section>

<section class="painel">
<h2>Motoristas cadastrados</h2>
<div class="tabela-responsiva">
<table>
<thead><tr><th>Nome</th><th>CNH</th><th>Validade</th><th>Telefone</th><th>Disponibilidade</th><th>Veículo</th><th>Ação</th></tr></thead>
<tbody>
<?php foreach ($motoristas as $m): ?>
<tr>
<td><?=h($m['nome'])?></td><td><?=h($m['categoria_cnh'])?></td>
<td><?=h($m['validade_cnh'])?></td><td><?=h($m['telefone'])?></td>
<td><?=h($m['disponibilidade'])?></td><td><?=h($m['placa'] ?: 'Não vinculado')?></td>
<td><form method="POST" onsubmit="return confirm('Excluir este motorista?')">
<input type="hidden" name="acao" value="excluir">
<input type="hidden" name="id" value="<?=h($m['id'])?>">
<button class="botao-excluir" type="submit">Excluir</button>
</form></td>
</tr>
<?php endforeach; ?>
<?php if (!$motoristas): ?><tr><td colspan="7">Nenhum motorista cadastrado.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
</section>
</main>
</div>
</body>
</html>
