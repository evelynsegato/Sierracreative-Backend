<?php

require __DIR__ . '/../src/bootstrap.php';

// ---------- Mini executor de testes ----------
$total  = 0;
$falhas = 0;

function teste(string $nome, callable $corpo): void
{
    global $total, $falhas;
    $total++;

    try {
        $corpo();
        echo "[OK]    $nome\n";
    } catch (Throwable $e) {
        $falhas++;
        echo "[FALHA] $nome -> " . $e->getMessage() . "\n";
    }
}

function afirmar(bool $condicao, string $mensagem = 'A afirmação falhou'): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

// Apaga um usuário de teste (as solicitações dele somem junto, por causa do ON DELETE CASCADE)
function removerUsuario(int $id): void
{
    $stmt = Database::conectar()->prepare('DELETE FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function emailUnico(): string
{
    return 'teste_' . uniqid() . '@teste.com';
}

echo "=== Testes automatizados - Sierra Creative ===\n\n";

// ---------- Cadastro: casos inválidos ----------
teste('Cadastro rejeita senha curta', function () {
    $r = (new AuthService())->cadastrar(['nome' => 'Joao', 'email' => emailUnico(), 'senha' => '123']);
    afirmar(isset($r['erros']['senha']), 'deveria apontar erro na senha');
});

teste('Cadastro rejeita e-mail inválido', function () {
    $r = (new AuthService())->cadastrar(['nome' => 'Joao', 'email' => 'isso-nao-e-email', 'senha' => 'senha12345']);
    afirmar(isset($r['erros']['email']), 'deveria apontar erro no e-mail');
});

teste('Cadastro rejeita nome vazio', function () {
    $r = (new AuthService())->cadastrar(['nome' => '   ', 'email' => emailUnico(), 'senha' => 'senha12345']);
    afirmar(isset($r['erros']['nome']), 'deveria apontar erro no nome');
});

// ---------- Cadastro e login: casos válidos ----------
teste('Cadastro válido guarda hash (nunca a senha) e permite login', function () {
    $auth  = new AuthService();
    $email = emailUnico();
    $r     = $auth->cadastrar(['nome' => 'Usuario Teste', 'email' => $email, 'senha' => 'senha12345']);

    try {
        afirmar(isset($r['id']), 'o cadastro deveria dar certo');

        $usuario = (new UsuarioRepository())->buscarPorEmail($email);
        afirmar($usuario['senha_hash'] !== 'senha12345', 'a senha não pode ser gravada pura');
        afirmar(str_starts_with($usuario['senha_hash'], '$2y$'), 'deveria ser um hash bcrypt');
        afirmar($usuario['perfil'] === 'cliente', 'todo cadastro novo deve ser cliente');

        afirmar($auth->autenticar($email, 'senha12345') !== null, 'senha certa deveria logar');
        afirmar($auth->autenticar($email, 'senhaerrada1') === null, 'senha errada não deveria logar');
    } finally {
        if (isset($r['id'])) {
            removerUsuario($r['id']);
        }
    }
});

teste('Cadastro rejeita e-mail já cadastrado', function () {
    $auth  = new AuthService();
    $email = emailUnico();
    $r     = $auth->cadastrar(['nome' => 'Usuario Teste', 'email' => $email, 'senha' => 'senha12345']);

    try {
        $repetido = $auth->cadastrar(['nome' => 'Outro Nome', 'email' => $email, 'senha' => 'senha12345']);
        afirmar(isset($repetido['erros']['email']), 'deveria recusar o e-mail repetido');
    } finally {
        if (isset($r['id'])) {
            removerUsuario($r['id']);
        }
    }
});

teste('Login com e-mail inexistente é recusado', function () {
    afirmar((new AuthService())->autenticar('nao-existe@teste.com', 'senha12345') === null);
});

// ---------- Solicitações: casos inválidos ----------
teste('Solicitação rejeita telefone, tipo e mensagem inválidos', function () {
    $r = (new SolicitacaoService())->criar(1, ['telefone' => '123', 'tipo_servico' => 'drone', 'mensagem' => 'oi']);
    afirmar(isset($r['erros']['telefone']), 'faltou erro de telefone');
    afirmar(isset($r['erros']['tipo_servico']), 'faltou erro de tipo');
    afirmar(isset($r['erros']['mensagem']), 'faltou erro de mensagem');
});

teste('Mudança de status rejeita valor desconhecido', function () {
    $r = (new SolicitacaoService())->mudarStatus(1, ['status' => 'banana']);
    afirmar(isset($r['erros']['status']), 'deveria recusar o status inválido');
});

// ---------- Solicitações: regras de negócio (válido e inválido) ----------
teste('Solicitação válida tem o telefone sanitizado e respeita as regras de dono e status', function () {
    $auth    = new AuthService();
    $servico = new SolicitacaoService();
    $email   = emailUnico();
    $cad     = $auth->cadastrar(['nome' => 'Dono Teste', 'email' => $email, 'senha' => 'senha12345']);

    try {
        $donoId = $cad['id'];
        $dono   = ['id' => $donoId, 'perfil' => 'cliente'];

        // Caso válido: cria, e o telefone fica só com números
        $criada = $servico->criar($donoId, [
            'telefone'     => '(15) 99800-7700',
            'tipo_servico' => 'videomaker',
            'mensagem'     => 'Quero um video do meu aniversario',
        ]);
        afirmar(isset($criada['id']), 'a solicitação deveria ser criada');

        $gravada = $servico->buscar($criada['id']);
        afirmar($gravada['telefone'] === '15998007700', 'o telefone deveria ser sanitizado');
        afirmar($gravada['status'] === 'pendente', 'o status inicial deveria ser pendente');

        $novosDados = [
            'telefone'     => '15998007700',
            'tipo_servico' => 'storymaker',
            'mensagem'     => 'Mensagem editada pelo dono',
        ];

        // Caso inválido: outro cliente não pode editar
        $intruso = ['id' => $donoId + 100000, 'perfil' => 'cliente'];
        $r = $servico->atualizar($intruso, $criada['id'], $novosDados);
        afirmar(isset($r['falha']) && $r['falha'][1] === 403, 'outro cliente deveria receber 403');

        // Caso válido: o dono edita enquanto está pendente
        $r = $servico->atualizar($dono, $criada['id'], $novosDados);
        afirmar(isset($r['ok']), 'o dono deveria conseguir editar');

        // Depois de cancelada, o dono não edita mais (409)
        $servico->cancelar($dono, $criada['id']);
        $r = $servico->atualizar($dono, $criada['id'], $novosDados);
        afirmar(isset($r['falha']) && $r['falha'][1] === 409, 'solicitação cancelada deveria dar 409');
    } finally {
        if (isset($cad['id'])) {
            removerUsuario($cad['id']);
        }
    }
});

// ---------- Resultado ----------
echo "\n";
echo "Total: $total | Passaram: " . ($total - $falhas) . " | Falharam: $falhas\n";

exit($falhas === 0 ? 0 : 1);