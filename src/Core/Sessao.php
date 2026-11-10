<?php

class Sessao
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,          // some ao fechar o navegador
            'path'     => '/',
            'httponly' => true,       // JavaScript não consegue ler o cookie (protege contra XSS)
            'samesite' => 'Lax',      // ajuda contra CSRF
        ]);

        session_start();
    }

    public static function logar(array $usuario): void
    {
        // Gera um novo código de sessão ao logar (evita "session fixation")
        session_regenerate_id(true);

        // Novo token CSRF para a sessão logada
        Csrf::renovar();

        $_SESSION['usuario'] = [
            'id'     => (int) $usuario['id'],
            'nome'   => $usuario['nome'],
            'perfil' => $usuario['perfil'],
        ];
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function destruir(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
    }
}