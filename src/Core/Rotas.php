<?php

class Rotas
{
    private array $rotas = [];

    public function adicionar(string $metodo, string $padrao, callable $acao): void
    {
        // Transforma "/solicitacoes/{id}" em uma expressão que aceita "/solicitacoes/3"
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[0-9]+)', $padrao) . '$#';
        $this->rotas[] = [$metodo, $regex, $acao];
    }

    public function executar(string $metodo, string $caminho): void
    {
        $caminho = '/' . trim($caminho, '/');
        $caminhoExiste = false;

        foreach ($this->rotas as [$metodoRota, $regex, $acao]) {
            if (preg_match($regex, $caminho, $achados)) {
                $caminhoExiste = true;

                if ($metodoRota === $metodo) {
                    // Pega só os parâmetros com nome (ex.: o "id")
                    $parametros = array_filter($achados, 'is_string', ARRAY_FILTER_USE_KEY);
                    $acao(...array_values($parametros));
                    return;
                }
            }
        }

        if ($caminhoExiste) {
            Resposta::erro('Método não permitido para esta rota.', 405);
        }

        Resposta::erro('Rota não encontrada.', 404);
    }
}