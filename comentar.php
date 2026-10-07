<?php
// Alteração: inicia sessão para validar CSRF e carrega as rotinas comuns.
session_start();
require_once __DIR__ . '/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    encerrarComErro('Método não permitido.', 405);
}

// Alteração: bloqueia requisições forjadas e índices inválidos.
if (!csrfValido($_POST['csrf_token'] ?? null)) {
    encerrarComErro('Formulário expirado. Tente novamente.', 403);
}
$id = obterIndice($_POST['id'] ?? null);
$nome = trim((string) ($_POST['nome'] ?? ''));
$mensagem = trim((string) ($_POST['mensagem'] ?? ''));
if ($id === null || $nome === '' || $mensagem === '') {
    encerrarComErro('Preencha o nome e a mensagem do comentário.');
}

try {
    $topicos = carregarXml(ARQUIVO_TOPICOS, 'topicos');
    // Alteração: confirma que o tópico existe antes de incluir o comentário.
    if (!isset($topicos->topico[$id])) {
        encerrarComErro('Tópico não encontrado.', 404);
    }
    if (!isset($topicos->topico[$id]->comentarios)) {
        $topicos->topico[$id]->addChild('comentarios');
    }
    $comentario = $topicos->topico[$id]->comentarios->addChild('comentario');
    adicionarTextoXml($comentario, 'nome', $nome);
    adicionarTextoXml($comentario, 'mensagem', $mensagem);
    salvarXml($topicos, ARQUIVO_TOPICOS);
    header('Location: listar.php');
    exit;
} catch (RuntimeException $excecao) {
    encerrarComErro($excecao->getMessage(), 500);
}