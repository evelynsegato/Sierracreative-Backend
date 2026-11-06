<?php

class Logger
{
    public static function info(string $evento, array $contexto = []): void
    {
        self::escrever('INFO', $evento, $contexto);
    }

    public static function aviso(string $evento, array $contexto = []): void
    {
        self::escrever('WARNING', $evento, $contexto);
    }

    public static function erro(string $evento, array $contexto = []): void
    {
        self::escrever('ERROR', $evento, $contexto);
    }

    private static function escrever(string $nivel, string $evento, array $contexto): void
    {
        $pasta = RAIZ . '/logs';

        if (!is_dir($pasta)) {
            @mkdir($pasta, 0775, true);
        }

        $partes = [];

        foreach ($contexto as $chave => $valor) {
            // Remove quebras de linha e aspas (evita forjar linhas no log)
            $valor = preg_replace('/[\r\n\t]+/', ' ', (string) $valor);
            $valor = str_replace('"', "'", $valor);
            $valor = mb_substr($valor, 0, 200);

            $partes[] = $chave . '="' . $valor . '"';
        }

        $linha = sprintf(
            "[%s] %s %s %s\n",
            date('Y-m-d H:i:s'),
            $nivel,
            $evento,
            implode(' ', $partes)
        );

        // O @ evita que um problema de gravação derrube o sistema
        @file_put_contents($pasta . '/app.log', $linha, FILE_APPEND | LOCK_EX);
    }
}