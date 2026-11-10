<?php

class SolicitacaoService
{
    private const TIPOS  = ['videomaker', 'storymaker'];
    private const STATUS = ['pendente', 'em_andamento', 'concluida', 'cancelada'];

    private SolicitacaoRepository $solicitacoes;

    public function __construct()
    {
        $this->solicitacoes = new SolicitacaoRepository();
    }

    public function criar(int $usuarioId, array $entrada): array
    {
        $validacao = $this->validar($entrada);

        if (isset($validacao['erros'])) {
            return $validacao;
        }

        $d = $validacao['dados'];
        $id = $this->solicitacoes->criar($usuarioId, $d['telefone'], $d['tipo'], $d['mensagem']);

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

    public function atualizar(array $usuario, int $id, array $entrada): array
    {
        $verificacao = $this->podeAlterar($usuario, $id);

        if (isset($verificacao['falha'])) {
            return $verificacao;
        }

        $validacao = $this->validar($entrada);

        if (isset($validacao['erros'])) {
            return $validacao;
        }

        $d = $validacao['dados'];
        $this->solicitacoes->atualizar($id, $d['telefone'], $d['tipo'], $d['mensagem']);

        return ['ok' => true];
    }

    public function cancelar(array $usuario, int $id): array
    {
        $verificacao = $this->podeAlterar($usuario, $id);

        if (isset($verificacao['falha'])) {
            return $verificacao;
        }

        $this->solicitacoes->atualizarStatus($id, 'cancelada');

        return ['ok' => true];
    }

    public function mudarStatus(int $id, array $entrada): array
    {
        $status = (string) ($entrada['status'] ?? '');

        if (!in_array($status, self::STATUS, true)) {
            return ['erros' => ['status' => 'Use: ' . implode(', ', self::STATUS) . '.']];
        }

        if ($this->solicitacoes->buscarPorId($id) === null) {
            return ['falha' => ['Solicitação não encontrada.', 404]];
        }

        $this->solicitacoes->atualizarStatus($id, $status);

        return ['ok' => true];
    }

    public function excluir(int $id): array
    {
        if ($this->solicitacoes->buscarPorId($id) === null) {
            return ['falha' => ['Solicitação não encontrada.', 404]];
        }

        $this->solicitacoes->excluir($id);

        return ['ok' => true];
    }

    /**
     * Regra de quem pode editar/cancelar:
     * - admin: qualquer solicitação
     * - cliente: só a sua, e só enquanto estiver pendente
     */
    private function podeAlterar(array $usuario, int $id): array
    {
        $solicitacao = $this->solicitacoes->buscarPorId($id);

        if ($solicitacao === null) {
            return ['falha' => ['Solicitação não encontrada.', 404]];
        }

        if ($usuario['perfil'] === 'admin') {
            return ['ok' => true];
        }

        if ((int) $solicitacao['usuario_id'] !== $usuario['id']) {
            return ['falha' => ['Você não tem permissão para alterar esta solicitação.', 403]];
        }

        if ($solicitacao['status'] !== 'pendente') {
            return ['falha' => ['Só é possível alterar solicitações pendentes.', 409]];
        }

        return ['ok' => true];
    }

    /**
     * Valida e limpa os dados de entrada (usado ao criar e ao editar).
     * Devolve ['erros' => [...]] ou ['dados' => [...]].
     */
    private function validar(array $entrada): array
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

        return ['dados' => ['telefone' => $telefone, 'tipo' => $tipo, 'mensagem' => $mensagem]];
    }
}