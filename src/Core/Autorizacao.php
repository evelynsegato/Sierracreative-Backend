<?php

class Autorizacao
{
    // Só passa quem está logado
    public static function exigirLogin(): array
    {
        $usuario = Sessao::usuario();

        if ($usuario === null) {
            Resposta::erro('Você precisa estar logado.', 401);
        }

        return $usuario;
    }

    // Só passa quem está logado E tem um dos perfis permitidos
    public static function exigirPerfil(string ...$perfis): array
    {
        $usuario = self::exigirLogin();

        if (!in_array($usuario['perfil'], $perfis, true)) {
            Resposta::erro('Você não tem permissão para esta ação.', 403);
        }

        return $usuario;
    }
}