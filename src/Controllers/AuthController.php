<?php

class AuthController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function cadastrar(): void
    {
        $entrada = json_decode(file_get_contents('php://input'), true);

        if (!is_array($entrada)) {
            Resposta::erro('Envie os dados em formato JSON.', 400);
        }

        $resultado = $this->auth->cadastrar($entrada);

        if (isset($resultado['erros'])) {
            Resposta::erro('Dados inválidos.', 422, $resultado['erros']);
        }

        Logger::info('usuario_criado', [
            'usuario_id' => $resultado['id'],
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        Resposta::sucesso(['id' => $resultado['id']], 201);
    }

    public function login(): void
    {
        $entrada = json_decode(file_get_contents('php://input'), true);

        if (!is_array($entrada)) {
            Resposta::erro('Envie os dados em formato JSON.', 400);
        }

        $email = (string) ($entrada['email'] ?? '');
        $senha = (string) ($entrada['senha'] ?? '');

        $usuario = $this->auth->autenticar($email, $senha);

        if ($usuario === null) {
            // Registramos o e-mail tentado, nunca a senha
            Logger::aviso('falha_login', [
                'email' => $email,
                'ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);

            Resposta::erro('E-mail ou senha incorretos.', 401);
        }

        Sessao::logar($usuario);

        Logger::info('login_sucesso', [
            'usuario_id' => $usuario['id'],
            'perfil'     => $usuario['perfil'],
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        Resposta::sucesso([
            'nome'   => $usuario['nome'],
            'perfil' => $usuario['perfil'],
        ]);
    }

    public function logout(): void
    {
        Sessao::destruir();
        Resposta::sucesso(['mensagem' => 'Sessão encerrada.']);
    }

    public function eu(): void
    {
        $usuario = Sessao::usuario();

        if ($usuario === null) {
            Resposta::erro('Você precisa estar logado.', 401);
        }

        Resposta::sucesso($usuario);
    }
}