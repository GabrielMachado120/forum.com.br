<?php
// Nova página: prepara a sessão, as funções auxiliares e os dados da listagem.
session_start();
require_once __DIR__ . '/funcoes.php';

$erro = '';
$mensagemSucesso = (string) ($_SESSION['mensagem_sucesso'] ?? '');
unset($_SESSION['mensagem_sucesso']);

try {
    $topicos = carregarXml(ARQUIVO_TOPICOS, 'topicos');
} catch (RuntimeException $excecao) {
    $erro = $excecao->getMessage();
    $topicos = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><topicos/>');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fórum</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #f2f2f2; color: #111; font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5; margin: 0; padding: 1.5rem;
        }
        main {
            background: #fff; border: 1px solid #d9d9d9; border-radius: 12px;
            box-shadow: 0 12px 36px rgb(0 0 0 / 8%); margin: 5vh auto;
            max-width: 1000px; padding: 2rem; width: 100%;
        }
        h1 { font-size: 1.75rem; margin: 0 0 1.5rem; }
        h2 { font-size: 1.4rem; margin: 0 0 0.75rem; }
        h3 { font-size: 1.1rem; margin: 1.5rem 0 0.75rem; }
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
        button.botao-secundario { background: #fff; color: #111; width: auto; }
        button.botao-secundario:hover { background: #111; color: #fff; }
        a { color: #111; font-weight: 600; text-underline-offset: 3px; }
        a:hover { text-decoration-thickness: 2px; }
        nav {
            align-items: center; border-bottom: 1px solid #d9d9d9; display: flex;
            flex-wrap: wrap; gap: 0.5rem 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem;
        }
        article { border: 1px solid #d9d9d9; border-radius: 8px; margin: 1.25rem 0; padding: 1.25rem; }
        article p { overflow-wrap: anywhere; }
        article section {
            background: #f7f7f7; border-left: 3px solid #111; border-radius: 0 6px 6px 0;
            margin: 0.75rem 0; padding: 0.75rem 1rem;
        }
        article section p { margin: 0; }
        article section form { margin-top: 0.75rem; }
        .mensagem {
            border: 1px solid #111; border-radius: 6px; margin: 0 0 1.25rem; padding: 0.75rem;
        }
        .mensagem-sucesso { background: #f2f2f2; }
        .mensagem-erro { background: #fff; }
        @media (max-width: 480px) {
            body { padding: 1rem; }
            main, article { padding: 1rem; }
        }
    </style>
</head>
<body>
    <main class="largo">
        <header>
            <h1>Tópicos do fórum</h1>
            <nav>
                <?php // Nova página: mostra as ações adequadas ao estado de autenticação. ?>
                <?php if (isset($_SESSION['usuario'])): ?>
                    <span>Conectado como <?= escapar($_SESSION['usuario']) ?></span>
                    <a href="criar_topico.php">Criar tópico</a>
                <?php else: ?>
                    <a href="login.php">Entrar</a>
                    <a href="cadastro.php">Cadastrar</a>
                <?php endif; ?>
            </nav>
        </header>

        <?php if ($mensagemSucesso !== ''): ?><p class="mensagem mensagem-sucesso"><?= escapar($mensagemSucesso) ?></p><?php endif; ?>
        <?php if ($erro !== ''): ?>
            <p class="mensagem mensagem-erro"><?= escapar($erro) ?></p>
        <?php elseif (count($topicos->topico) === 0): ?>
            <p class="mensagem">Nenhum tópico foi criado ainda.</p>
        <?php endif; ?>

        <?php // Nova página: lista tópicos e comentários sempre escapando o XML. ?>
        <?php // Alteração: usa contador porque o SimpleXML devolve o nome da tag como chave. ?>
        <?php $id = 0; ?>
        <?php foreach ($topicos->topico as $topico): ?>
            <article>
                <h2><?= escapar($topico->titulo) ?></h2>
                <p><?= nl2br(escapar($topico->mensagem)) ?></p>
                <p><small>Autor: <?= escapar($topico->autor) ?></small></p>

                <h3>Comentários</h3>
                <?php if (count($topico->comentarios->comentario) === 0): ?>
                    <p>Este tópico ainda não possui comentários.</p>
                <?php endif; ?>

                <?php $comentarioId = 0; ?>
                <?php foreach ($topico->comentarios->comentario as $comentario): ?>
                    <section>
                        <p><strong><?= escapar($comentario->nome) ?>:</strong> <?= nl2br(escapar($comentario->mensagem)) ?></p>
                        <?php // Nova página: o autor recebe um formulário POST seguro para exclusão. ?>
                        <?php if (isset($_SESSION['usuario']) && (string) $_SESSION['usuario'] === (string) $topico->autor): ?>
                            <form method="post" action="excluir.php">
                                <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
                                <input type="hidden" name="id" value="<?= escapar($id) ?>">
                                <input type="hidden" name="comentario" value="<?= escapar($comentarioId) ?>">
                                <button class="botao-secundario" type="submit">Excluir comentário</button>
                            </form>
                        <?php endif; ?>
                    </section>
                    <?php // Alteração: avança o índice para o próximo comentário. ?>
                    <?php $comentarioId++; ?>
                <?php endforeach; ?>

                <?php // Nova página: disponibiliza o formulário de comentário com proteção CSRF. ?>
                <form method="post" action="comentar.php">
                    <input type="hidden" name="csrf_token" value="<?= escapar(tokenCsrf()) ?>">
                    <input type="hidden" name="id" value="<?= escapar($id) ?>">
                    <label>Nome: <input type="text" name="nome" required></label>
                    <label>Comentário: <textarea name="mensagem" required></textarea></label>
                    <button type="submit">Comentar</button>
                </form>
            </article>
            <?php // Alteração: avança o índice usado pelo próximo tópico. ?>
            <?php $id++; ?>
        <?php endforeach; ?>
    </main>
</body>
</html>