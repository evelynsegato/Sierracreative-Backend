<?php

// Pasta raiz do projeto (sierra-creative)
define('RAIZ', dirname(__DIR__));

date_default_timezone_set('America/Sao_Paulo');

// Autoload: quando o PHP encontra "new Database()", procura o arquivo sozinho
spl_autoload_register(function (string $classe) {
    $pastas = ['Core', 'Controllers', 'Services', 'Repositories'];

    foreach ($pastas as $pasta) {
        $arquivo = RAIZ . "/src/$pasta/$classe.php";

        if (file_exists($arquivo)) {
            require $arquivo;
            return;
        }
    }
});

Sessao::iniciar();