<?php

require __DIR__ . '/../src/bootstrap.php';

try {
    $rotas = new Rotas();

    $auth = new AuthController();
    $solicitacoes = new SolicitacaoController();

    // Autenticação
    $rotas->adicionar('POST', '/auth/cadastro', fn() => $auth->cadastrar());
    $rotas->adicionar('POST', '/auth/login',    fn() => $auth->login());
    $rotas->adicionar('POST', '/auth/logout',   fn() => $auth->logout());
    $rotas->adicionar('GET',  '/auth/eu',       fn() => $auth->eu());

    // Solicitações
    $rotas->adicionar('POST',   '/solicitacoes',                 fn() => $solicitacoes->criar());
    $rotas->adicionar('GET',    '/solicitacoes',                 fn() => $solicitacoes->listar());
    $rotas->adicionar('GET',    '/solicitacoes/{id}',            fn($id) => $solicitacoes->ver((int) $id));
    $rotas->adicionar('PUT',    '/solicitacoes/{id}',            fn($id) => $solicitacoes->atualizar((int) $id));
    $rotas->adicionar('POST',   '/solicitacoes/{id}/cancelar',   fn($id) => $solicitacoes->cancelar((int) $id));
    $rotas->adicionar('PATCH',  '/solicitacoes/{id}/status',     fn($id) => $solicitacoes->mudarStatus((int) $id));
    $rotas->adicionar('DELETE', '/solicitacoes/{id}',            fn($id) => $solicitacoes->excluir((int) $id));

    $rotas->executar($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'] ?? '/');
} catch (Throwable $e) {
    // Erro inesperado: detalhes técnicos só no modo de desenvolvimento
    $detalhes = [];

    if (Config::get('APP_ENV') === 'development') {
        $detalhes = ['tecnico' => $e->getMessage()];
    }

    Resposta::erro('Erro interno do servidor.', 500, $detalhes);
}