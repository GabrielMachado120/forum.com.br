<?php
// Alteração: inicializa a sessão e carrega as rotinas comuns.
session_start();
require_once __DIR__ . '/funcoes.php';

$email = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Alteração: valida a entrada e trata arquivo XML vazio ou inválido.
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    try {
        $usuarios = carregarXml(ARQUIVO_USUARIOS, 'usuarios');
        foreach ($usuarios->usuario as $usuario) {
            $hash = (string) $usuario->senha;
            // Alteração: aceita registros antigos em MD5 e os atualiza após o login.
            $senhaCorreta = password_verify($senha, $hash)
                || (preg_match('/^[a-f0-9]{32}$/i', $hash) === 1 && hash_equals(strtolower($hash), md5($senha)));
            if (strcasecmp((string) $usuario->email, $email) === 0 && $senhaCorreta) {
                session_regenerate_id(true);
                $_SESSION['usuario'] = (string) $usuario->email;
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $usuario->senha = password_hash($senha, PASSWORD_DEFAULT);
                    salvarXml($usuarios, ARQUIVO_USUARIOS);
                }
                // Alteração: redireciona após o POST para evitar reenvio do formulário.
                header('Location: listar.php');
                exit;
            }
        }
        $erro = 'Login inválido.';
    } catch (RuntimeException $excecao) {
        $erro = $excecao->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #f2f2f2; color: #111; font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5; margin: 0; padding: 1.5rem;
        }
        main {
            background: #fff; border: 1px solid #d9d9d9; border-radius: 12px;
            box-shadow: 0 12px 36px rgb(0 0 0 / 8%); margin: 5vh auto;
            max-width: 440px; padding: 2rem; width: 100%;
        }
        h1 { font-size: 1.75rem; margin: 0 0 1.5rem; }
        label { display: block; font-size: 0.95rem; font-weight: 600; margin-bottom: 1rem; }
        input {
            background: #fff; border: 1px solid #999; border-radius: 6px; color: #111;
            display: block; font: inherit; margin-top: 0.35rem; padding: 0.7rem 0.8rem; width: 100%;
        }
        input:focus { border-color: #111; outline: 2px solid #111; outline-offset: 2px; }
        form { margin: 0; }
        button {
            background: #111; border: 1px solid #111; border-radius: 6px; color: #fff;
            cursor: pointer; font: inherit; font-weight: 600; padding: 0.75rem 1rem; width: 100%;
        }
        button:hover { background: #fff; color: #111; }
        a { color: #111; font-weight: 600; text-underline-offset: 3px; }
        a:hover { text-decoration-thickness: 2px; }
        .mensagem {
            border: 1px solid #111; border-radius: 6px; margin: 0 0 1.25rem; padding: 0.75rem;
        }
        .mensagem-erro { background: #fff; }
        .link-secundario { margin: 1.25rem 0 0; text-align: center; }
        @media (max-width: 480px) { main { padding: 1.5rem; } }
    </style>
</head>
<body>
    <main>
        <h1>Login</h1>
        <?php // Alteração: mostra o erro com segurança e corrige os textos corrompidos. ?>
        <?php if ($erro !== ''): ?><p class="mensagem mensagem-erro"><?= escapar($erro) ?></p><?php endif; ?>
        <form method="post">
            <label>E-mail: <input type="email" name="email" value="<?= escapar($email) ?>" required></label>
            <label>Senha: <input type="password" name="senha" required></label>
            <button type="submit">Entrar</button>
        </form>
        <p class="link-secundario"><a href="cadastro.php">Criar cadastro</a> · <a href="listar.php">Ver tópicos</a></p>
    </main>
</body>
</html>