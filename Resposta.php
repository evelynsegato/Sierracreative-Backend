<?php

class Resposta
{
    public static function sucesso($dados = null, int $status = 200): void
    {
        self::enviar(['sucesso' => true, 'dados' => $dados], $status);
    }

    public static function erro(string $mensagem, int $status = 400, array $erros = []): void
    {
        $corpo = ['sucesso' => false, 'mensagem' => $mensagem];

        if ($erros) {
            $corpo['erros'] = $erros;
        }

        self::enviar($corpo, $status);
    }

    private static function enviar(array $corpo, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($corpo, JSON_UNESCAPED_UNICODE);
        exit;
    }
}