<?php

class Config
{
    private static ?array $dados = null;

    public static function get(string $chave, $padrao = null)
    {
        // Só lê o .env na primeira vez
        if (self::$dados === null) {
            self::$dados = require RAIZ . '/config/config.php';
        }

        return self::$dados[$chave] ?? $padrao;
    }
}