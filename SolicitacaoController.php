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
        $resultado = $this->servico->criar($usuario['id'], $this->lerJson());

        if (isset($resultado['id'])) {
            Logger::info('solicitacao_criada', [
                'solicitacao_id' => $resultado['id'],
                'usuario_id'     => $usuario['id'],
            ]);
        }

        $this->responder($resultado, 201, ['id' => $resultado['id'] ?? null]);
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

    public function atualizar(int $id): void
    {
        $usuario = Autorizacao::exigirLogin();
        $resultado = $this->servico->atualizar($usuario, $id, $this->lerJson());

        $this->responder($resultado, 200, ['mensagem' => 'Solicitação atualizada.']);
    }

    public function cancelar(int $id): void
    {
        $usuario = Autorizacao::exigirLogin();
        $resultado = $this->servico->cancelar($usuario, $id);

        $this->responder($resultado, 200, ['mensagem' => 'Solicitação cancelada.']);
    }

    public function mudarStatus(int $id): void
    {
        Autorizacao::exigirPerfil('admin');
        $resultado = $this->servico->mudarStatus($id, $this->lerJson());

        $this->responder($resultado, 200, ['mensagem' => 'Status atualizado.']);
    }

    public function excluir(int $id): void
    {
        Autorizacao::exigirPerfil('admin');
        $resultado = $this->servico->excluir($id);

        $this->responder($resultado, 200, ['mensagem' => 'Solicitação excluída.']);
    }

    // Lê o corpo da requisição (JSON) e devolve como lista
    private function lerJson(): array
    {
        $entrada = json_decode(file_get_contents('php://input'), true);

        if (!is_array($entrada)) {
            Resposta::erro('Envie os dados em formato JSON.', 400);
        }

        return $entrada;
    }

    // Traduz o resultado do Service em resposta HTTP
    private function responder(array $resultado, int $statusSucesso, array $dados): void
    {
        if (isset($resultado['erros'])) {
            Resposta::erro('Dados inválidos.', 422, $resultado['erros']);
        }

        if (isset($resultado['falha'])) {
            [$mensagem, $status] = $resultado['falha'];
            Resposta::erro($mensagem, $status);
        }

        Resposta::sucesso($dados, $statusSucesso);
    }
}