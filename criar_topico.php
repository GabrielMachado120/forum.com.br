<?php
// Alteração: usa caminhos absolutos e as rotinas comuns de XML.
session_start();
require_once __DIR__ . '/funcoes.php';

if (!isset($_SESSION['usuario'])) {
    encerrarComErro('Você precisa estar logado para criar um tópico.', 403);
}

$titulo = '';
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Alteração: valida o token e os campos antes de acessar o XML.
    if (!csrfValido($_POST['csrf_token'] ?? null)) {
        encerrarComErro('Formulário expirado. Tente novamente.', 403);
    }
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $mensagem = trim((string) ($_POST['mensagem'] ?? ''));
    if ($titulo === '' || $mensagem === '') {
        $erro = 'Informe o título e a mensagem.';
    } else {
        try {
            $topicos = carregarXml(ARQUIVO_TOPICOS, 'topicos');
            $novo = $topicos->addChild('topico');
            adicionarTextoXml($novo, 'autor', (string) $_SESSION['usuario']);
            adicionarTextoXml($novo, 'titulo', $titulo);
            adicionarTextoXml($novo, 'mensagem', $mensagem);
            $novo->addChild('comentarios');
            salvarXml($topicos, ARQUIVO_TOPICOS);
            // Alteração: evita a criação duplicada ao atualizar a página.
            $_SESSION['mensagem_sucesso'] = 'Tópico criado com sucesso!';
            header('Location: listar.php');
            exit;
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
    <title>Criar tópico</title>
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
        input, textarea {
            background: #fff; border: 1px solid #999; border-radius: 6px; color: #111;
            display: block; font: inherit; margin-top: 0.35rem; padding: 0.7rem 0.8rem; width: 100%;
        }
        textarea { min-height: 120px; resize: vertical; }
        input:focus, textarea:focus {
            border-color: #111; outline: 2px solid #111; outline-offset: 2px;
        }
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
        <h1>Criar tópico</h1>
        <?php // Alteração: inclui proteção CSRF e escapa os valores exibidos. ?>
        <?php if ($erro !== ''): ?><p class="mensagem mensagem-erro"><?= escapar($erro) ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
            <label>Título: <input type="text" name="titulo" value="<?= escapar($titulo) ?>" required></label>
            <label>Mensagem: <textarea name="mensagem" required><?= escapar($mensagem) ?></textarea></label>
            <button type="submit">Criar tópico</button>
        </form>
        <p class="link-secundario"><a href="listar.php">Voltar aos tópicos</a></p>
    </main>
</body>
</html>