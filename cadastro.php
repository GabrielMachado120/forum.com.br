<?php
// Alteração: usa as funções seguras de leitura, gravação e escape.
require_once __DIR__ . '/funcoes.php';

// Alteração: mantém os dados preenchidos e apresenta erros de validação.
$nome = '';
$celular = '';
$email = '';
$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Alteração: normaliza e valida todos os campos antes de gravá-los.
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $celular = trim((string) ($_POST['celular'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');

    if ($nome === '' || $celular === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Preencha nome, celular e um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve possuir pelo menos 6 caracteres.';
    } else {
        try {
            $usuarios = carregarXml(ARQUIVO_USUARIOS, 'usuarios');
            // Alteração: impede o cadastro duplicado do mesmo e-mail.
            foreach ($usuarios->usuario as $usuario) {
                if (strcasecmp((string) $usuario->email, $email) === 0) {
                    $erro = 'Este e-mail já está cadastrado.';
                    break;
                }
            }
            if ($erro === '') {
                $novo = $usuarios->addChild('usuario');
                adicionarTextoXml($novo, 'nome', $nome);
                adicionarTextoXml($novo, 'celular', $celular);
                adicionarTextoXml($novo, 'email', $email);
                // Alteração: substitui MD5 pelo algoritmo seguro de hash de senha do PHP.
                adicionarTextoXml($novo, 'senha', password_hash($senha, PASSWORD_DEFAULT));
                salvarXml($usuarios, ARQUIVO_USUARIOS);
                $sucesso = true;
            }
        } catch (RuntimeException $excecao) {
            $erro = $excecao->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #f2f2f2;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
            margin: 0;
            padding: 1.5rem;
        }

        main {
            background: #fff;
            border: 1px solid #d9d9d9;
            border-radius: 12px;
            box-shadow: 0 12px 36px rgb(0 0 0 / 8%);
            margin: 5vh auto;
            max-width: 440px;
            padding: 2rem;
            width: 100%;
        }

        h1 {
            font-size: 1.75rem;
            margin: 0 0 1.5rem;
        }

        label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        input,
        textarea {
            background: #fff;
            border: 1px solid #999;
            border-radius: 6px;
            color: #111;
            display: block;
            font: inherit;
            margin-top: 0.35rem;
            padding: 0.7rem 0.8rem;
            width: 100%;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            border-color: #111;
            outline: 2px solid #111;
            outline-offset: 2px;
        }

        form {
            margin: 0;
        }

        button {
            background: #111;
            border: 1px solid #111;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-weight: 600;
            padding: 0.75rem 1rem;
            width: 100%;
        }

        button:hover {
            background: #fff;
            color: #111;
        }

        a {
            color: #111;
            font-weight: 600;
            text-underline-offset: 3px;
        }

        a:hover {
            text-decoration-thickness: 2px;
        }

        .mensagem {
            border: 1px solid #111;
            border-radius: 6px;
            margin: 0 0 1.25rem;
            padding: 0.75rem;
        }

        .mensagem-sucesso {
            background: #f2f2f2;
        }

        .mensagem-erro {
            background: #fff;
        }

        .link-secundario {
            margin: 1.25rem 0 0;
            text-align: center;
        }

        @media (max-width: 480px) {
            main {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <main>
        <h1>Cadastro</h1>
        <?php if ($sucesso): ?>
            <p class="mensagem mensagem-sucesso">Usuário cadastrado com sucesso!</p>
            <p class="link-secundario"><a href="login.php">Fazer login</a></p>
        <?php else: ?>
            <?php // Alteração: escapa mensagens e valores devolvidos ao formulário. ?>
            <?php if ($erro !== ''): ?><p class="mensagem mensagem-erro"><?= escapar($erro) ?></p><?php endif; ?>
            <form method="post">
                <label>Nome: <input type="text" name="nome" value="<?= escapar($nome) ?>" required></label>
                <label>Celular: <input type="text" name="celular" value="<?= escapar($celular) ?>" required></label>
                <label>E-mail: <input type="email" name="email" value="<?= escapar($email) ?>" required></label>
                <label>Senha: <input type="password" name="senha" minlength="6" required></label>
                <button type="submit">Cadastrar</button>
            </form>
            <p class="link-secundario"><a href="login.php">Já tenho cadastro</a></p>
        <?php endif; ?>
    </main>
</body>
</html>