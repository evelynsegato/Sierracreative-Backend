<?php

class AuthService
{
    private UsuarioRepository $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
    }

    /**
     * Valida os dados e cadastra o usuário.
     * Retorna ['erros' => [...]] se algo estiver errado, ou ['id' => n] se deu certo.
     */
    public function cadastrar(array $entrada): array
    {
        $nome  = trim((string) ($entrada['nome'] ?? ''));
        $email = strtolower(trim((string) ($entrada['email'] ?? '')));
        $senha = (string) ($entrada['senha'] ?? '');

        $erros = [];

        // Nome: obrigatório, entre 2 e 100 caracteres
        if (mb_strlen($nome) < 2 || mb_strlen($nome) > 100) {
            $erros['nome'] = 'O nome deve ter entre 2 e 100 caracteres.';
        }

        // E-mail: formato válido e até 150 caracteres
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $erros['email'] = 'Informe um e-mail válido.';
        }

        // Senha: entre 8 e 72 caracteres (72 é o limite do bcrypt)
        if (strlen($senha) < 8 || strlen($senha) > 72) {
            $erros['senha'] = 'A senha deve ter entre 8 e 72 caracteres.';
        }

        if ($erros) {
            return ['erros' => $erros];
        }

        // E-mail já usado?
        if ($this->usuarios->buscarPorEmail($email) !== null) {
            return ['erros' => ['email' => 'Este e-mail já está cadastrado.']];
        }

        // Embaralha a senha (hash seguro, exigido pelo professor)
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $id = $this->usuarios->criar($nome, $email, $hash);

        return ['id' => $id];
    }

    /**
     * Confere e-mail e senha.
     * Retorna o usuário se estiver certo, ou null se estiver errado.
     */
    public function autenticar(string $email, string $senha): ?array
    {
        $email   = strtolower(trim($email));
        $usuario = $this->usuarios->buscarPorEmail($email);

        // Mesma resposta para "e-mail não existe" e "senha errada",
        // para não revelar quais e-mails estão cadastrados.
        if ($usuario === null || !password_verify($senha, $usuario['senha_hash'])) {
            return null;
        }

        return $usuario;
    }
}