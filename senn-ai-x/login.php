
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'conexao.php';

if (isset($_GET['sair'])) {
    $_SESSION = [];
    session_destroy();

    header('Location: login.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
        $erro = 'Informe um e-mail válido e uma senha.';
    } elseif ($acao === 'cadastrar') {
        $nome = trim($_POST['nome'] ?? '');
        $total = (int) $pdo->query(
            'SELECT COUNT(*) FROM usuarios'
        )->fetchColumn();

        if ($total > 0) {
            $erro = 'O cadastro inicial já foi realizado. Entre com sua conta.';
        } elseif ($nome === '') {
            $erro = 'Informe seu nome.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha)
                 VALUES (?, ?, ?)'
            );

            try {
                $stmt->execute([
                    $nome,
                    $email,
                    password_hash($senha, PASSWORD_DEFAULT)
                ]);

                $_SESSION['usuario_id'] = $pdo->lastInsertId();
                $_SESSION['usuario_nome'] = $nome;
                $_SESSION['perfil'] = 'Administrador';

                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                $erro = 'Não foi possível cadastrar. Verifique o e-mail.';
            }
        }
    } elseif ($acao === 'entrar') {
        $stmt = $pdo->prepare(
            'SELECT * FROM usuarios WHERE email = ?'
        );
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['perfil'] = $usuario['perfil'];

            header('Location: index.php');
            exit;
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}

$total = (int) $pdo->query(
    'SELECT COUNT(*) FROM usuarios'
)->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | SENN AI-X</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-login">
    <main class="caixa-login">
        <h1>SENN <span>AI-X</span></h1>
        <p>Gestão inteligente de logística</p>

        <?php if ($erro): ?>
            <p class="mensagem erro">
                <?= htmlspecialchars($erro) ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <?php if ($total === 0): ?>
                <h2>Criar administrador</h2>
                <input type="hidden" name="acao" value="cadastrar">

                <label for="nome">Nome</label>
                <input id="nome" name="nome" required maxlength="100">
            <?php else: ?>
                <h2>Entrar no sistema</h2>
                <input type="hidden" name="acao" value="entrar">
            <?php endif; ?>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email"
                   required maxlength="150">

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha"
                   required>

            <button type="submit">
                <?= $total === 0 ? 'Criar conta' : 'Entrar' ?>
            </button>
        </form>
    </main>
</body>
</html>
