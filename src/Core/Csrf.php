<?php

class Csrf
{
    // Devolve o token da sessão (cria um se ainda não existir)
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf'];
    }

    // Troca o token por um novo (usado quando a pessoa faz login)
    public static function renovar(): void
    {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    // Recusa pedidos que alteram dados sem o token correto
    public static function verificar(): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'];

        // Leitura não altera nada, então não precisa de token
        if (in_array($metodo, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $esperado = $_SESSION['csrf'] ?? '';
        $enviado  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        // hash_equals compara de forma segura contra ataques de tempo
        if ($esperado === '' || !hash_equals($esperado, $enviado)) {
            Resposta::erro('Token CSRF inválido ou ausente.', 403);
        }
    }
}