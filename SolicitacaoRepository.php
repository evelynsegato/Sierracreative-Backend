<?php

class SolicitacaoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function criar(int $usuarioId, string $telefone, string $tipo, string $mensagem): int
    {
        $sql = 'INSERT INTO solicitacoes (usuario_id, telefone, tipo_servico, mensagem)
                VALUES (:usuario_id, :telefone, :tipo, :mensagem)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':telefone'   => $telefone,
            ':tipo'       => $tipo,
            ':mensagem'   => $mensagem,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function listarTodas(): array
    {
        $sql = 'SELECT s.id, s.usuario_id, u.nome AS cliente, s.telefone, s.tipo_servico,
                       s.mensagem, s.status, s.criado_em
                FROM solicitacoes s
                JOIN usuarios u ON u.id = s.usuario_id
                ORDER BY s.criado_em DESC';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = 'SELECT id, usuario_id, telefone, tipo_servico, mensagem, status, criado_em
                FROM solicitacoes
                WHERE usuario_id = :usuario_id
                ORDER BY criado_em DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, usuario_id, telefone, tipo_servico, mensagem, status, criado_em
             FROM solicitacoes WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $linha = $stmt->fetch();
        return $linha ?: null;
    }

    public function atualizar(int $id, string $telefone, string $tipo, string $mensagem): void
    {
        $sql = 'UPDATE solicitacoes
                SET telefone = :telefone, tipo_servico = :tipo, mensagem = :mensagem
                WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':telefone' => $telefone,
            ':tipo'     => $tipo,
            ':mensagem' => $mensagem,
            ':id'       => $id,
        ]);
    }

    public function atualizarStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE solicitacoes SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM solicitacoes WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}