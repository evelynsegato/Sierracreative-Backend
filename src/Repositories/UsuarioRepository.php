<?php

class UsuarioRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql = 'SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = :email';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    public function criar(string $nome, string $email, string $senhaHash): int
    {
        $sql = 'INSERT INTO usuarios (nome, email, senha_hash) VALUES (:nome, :email, :senha_hash)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nome'       => $nome,
            ':email'      => $email,
            ':senha_hash' => $senhaHash,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}