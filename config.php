<?php

// Lê o arquivo .env e devolve as configurações em um array
function carregarEnv(string $caminho): array
{
    if (!file_exists($caminho)) {
        throw new RuntimeException('Arquivo .env não encontrado.');
    }

    $config = [];
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        // ignora comentários (linhas que começam com #)
        if ($linha === '' || $linha[0] === '#') {
            continue;
        }

        // separa "CHAVE=valor" em duas partes
        [$chave, $valor] = array_pad(explode('=', $linha, 2), 2, '');
        $config[trim($chave)] = trim($valor);
    }

    return $config;
}

return carregarEnv(__DIR__ . '/../.env');