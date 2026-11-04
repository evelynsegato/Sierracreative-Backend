<?php

class SolicitacaoController
{
    private SolicitacaoService $servico;

    public function __construct()
    {
        $this->servico = new SolicitacaoService();
    }

    public function criar(): void
    {
        $usuario = Autorizacao::exigirPerfil('cliente', 'admin');

        $entrada = json_decode(file_get_contents('php://input'), true);

        if (!is_array($entrada)) {
            Resposta::erro('Envie os dados em formato JSON.', 400);
        }

        $resultado = $this->servico->criar($usuario['id'], $entrada);

        if (isset($resultado['erros'])) {
            Resposta::erro('Dados inválidos.', 422, $resultado['erros']);
        }

        Resposta::sucesso(['id' => $resultado['id']], 201);
    }

    public function listar(): void
    {
        $usuario = Autorizacao::exigirLogin();

        Resposta::sucesso($this->servico->listar($usuario));
    }

    public function ver(int $id): void
    {
        $usuario = Autorizacao::exigirLogin();

        $solicitacao = $this->servico->buscar($id);

        if ($solicitacao === null) {
            Resposta::erro('Solicitação não encontrada.', 404);
        }

        // Cliente só pode ver as suas
        if ($usuario['perfil'] !== 'admin' && (int) $solicitacao['usuario_id'] !== $usuario['id']) {
            Resposta::erro('Você não tem permissão para ver esta solicitação.', 403);
        }

        Resposta::sucesso($solicitacao);
    }
}