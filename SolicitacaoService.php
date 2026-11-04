<?php

class SolicitacaoService
{
    private const TIPOS = ['videomaker', 'storymaker'];

    private SolicitacaoRepository $solicitacoes;

    public function __construct()
    {
        $this->solicitacoes = new SolicitacaoRepository();
    }

    public function criar(int $usuarioId, array $entrada): array
    {
        // Sanitização: telefone fica só com números
        $telefone = preg_replace('/\D/', '', (string) ($entrada['telefone'] ?? ''));
        $tipo     = (string) ($entrada['tipo_servico'] ?? '');
        $mensagem = trim((string) ($entrada['mensagem'] ?? ''));

        $erros = [];

        if (strlen($telefone) < 10 || strlen($telefone) > 11) {
            $erros['telefone'] = 'O telefone deve ter 10 ou 11 números (com DDD).';
        }

        if (!in_array($tipo, self::TIPOS, true)) {
            $erros['tipo_servico'] = 'Escolha videomaker ou storymaker.';
        }

        if (mb_strlen($mensagem) < 10 || mb_strlen($mensagem) > 2000) {
            $erros['mensagem'] = 'A mensagem deve ter entre 10 e 2000 caracteres.';
        }

        if ($erros) {
            return ['erros' => $erros];
        }

        $id = $this->solicitacoes->criar($usuarioId, $telefone, $tipo, $mensagem);

        return ['id' => $id];
    }

    public function listar(array $usuario): array
    {
        if ($usuario['perfil'] === 'admin') {
            return $this->solicitacoes->listarTodas();
        }

        return $this->solicitacoes->listarPorUsuario($usuario['id']);
    }

    public function buscar(int $id): ?array
    {
        return $this->solicitacoes->buscarPorId($id);
    }
}