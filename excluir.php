<?php
// Alteração: inicia sessão e usa as rotinas comuns de segurança e XML.
session_start();
require_once __DIR__ . '/funcoes.php';

if (!isset($_SESSION['usuario'])) {
    encerrarComErro('Você precisa estar logado.', 403);
}

// Alteração: exclusão agora aceita apenas POST protegido por token CSRF.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    encerrarComErro('Método não permitido.', 405);
}
if (!csrfValido($_POST['csrf_token'] ?? null)) {
    encerrarComErro('Formulário expirado. Tente novamente.', 403);
}
$id = obterIndice($_POST['id'] ?? null);
$comentarioId = obterIndice($_POST['comentario'] ?? null);
if ($id === null || $comentarioId === null) {
    encerrarComErro('Comentário inválido.');
}

try {
    $topicos = carregarXml(ARQUIVO_TOPICOS, 'topicos');
    // Alteração: verifica a existência e a permissão antes de excluir.
    if (!isset($topicos->topico[$id])) {
        encerrarComErro('Tópico não encontrado.', 404);
    }
    if ((string) $topicos->topico[$id]->autor !== (string) $_SESSION['usuario']) {
        encerrarComErro('Somente o autor do tópico pode excluir comentários.', 403);
    }
    if (!isset($topicos->topico[$id]->comentarios->comentario[$comentarioId])) {
        encerrarComErro('Comentário não encontrado.', 404);
    }
    unset($topicos->topico[$id]->comentarios->comentario[$comentarioId]);
    salvarXml($topicos, ARQUIVO_TOPICOS);
    header('Location: listar.php');
    exit;
} catch (RuntimeException $excecao) {
    encerrarComErro($excecao->getMessage(), 500);
}