<?php

// Alteração: centraliza caminhos e corrige a leitura de arquivos XML vazios.
const ARQUIVO_USUARIOS = __DIR__ . DIRECTORY_SEPARATOR . 'usuarios.xml';
const ARQUIVO_TOPICOS = __DIR__ . DIRECTORY_SEPARATOR . 'topicos.xml';

function carregarXml(string $arquivo, string $raiz): SimpleXMLElement
{
    // Alteração: cria uma estrutura válida quando o arquivo ainda não tem conteúdo.
    $conteudo = is_file($arquivo) ? file_get_contents($arquivo) : false;
    if ($conteudo === false || trim($conteudo) === '') {
        return new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $raiz . '/>');
    }

    // Alteração: bloqueia acesso de rede pelo parser e detecta XML corrompido.
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($conteudo, SimpleXMLElement::class, LIBXML_NONET);
    libxml_clear_errors();
    if ($xml === false || $xml->getName() !== $raiz) {
        throw new RuntimeException('O arquivo de dados está corrompido ou possui uma estrutura inválida.');
    }
    return $xml;
}

function salvarXml(SimpleXMLElement $xml, string $arquivo): void
{
    // Alteração: grava com bloqueio e verifica falhas de escrita.
    $conteudo = $xml->asXML();
    if ($conteudo === false || file_put_contents($arquivo, $conteudo, LOCK_EX) === false) {
        throw new RuntimeException('Não foi possível salvar os dados.');
    }
}

function adicionarTextoXml(SimpleXMLElement $pai, string $nome, string $valor): SimpleXMLElement
{
    // Alteração: adiciona caracteres especiais sem interpretá-los como entidades XML.
    $filho = $pai->addChild($nome);
    $filho[0] = $valor;
    return $filho;
}

function escapar(mixed $valor): string
{
    // Alteração: impede injeção de HTML ao exibir dados salvos.
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function obterIndice(mixed $valor): ?int
{
    // Alteração: valida índices antes de acessar tópicos ou comentários.
    if (!is_string($valor) && !is_int($valor)) {
        return null;
    }
    $indice = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    return $indice === false ? null : $indice;
}

function tokenCsrf(): string
{
    // Alteração: gera proteção para formulários que modificam dados.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfValido(mixed $token): bool
{
    // Alteração: confere se o formulário foi emitido pela sessão atual.
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function encerrarComErro(string $mensagem, int $status = 400): never
{
    // Alteração: apresenta erros com status HTTP e saída segura.
    http_response_code($status);
    echo <<<'HTML'
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erro</title>
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
        a { color: #111; font-weight: 600; text-underline-offset: 3px; }
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
        <h1>Erro</h1>
        <p class="mensagem mensagem-erro">
HTML;
    echo escapar($mensagem);
    echo <<<'HTML'
        </p>
        <p class="link-secundario"><a href="listar.php">Voltar aos tópicos</a></p>
    </main>
</body>
</html>
HTML;
    exit;
}