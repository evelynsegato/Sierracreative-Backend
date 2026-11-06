// Central de acesso à API: cuida do token CSRF automaticamente.
let tokenCsrf = null;

async function obterToken(renovar = false) {
    if (tokenCsrf && !renovar) {
        return tokenCsrf;
    }

    const resposta = await fetch('api.php/auth/csrf');
    const corpo = await resposta.json();

    tokenCsrf = corpo.dados.token;
    return tokenCsrf;
}

async function enviar(caminho, metodo, dados) {
    const cabecalhos = {};
    const opcoes = { method: metodo, headers: cabecalhos };

    if (dados) {
        cabecalhos['Content-Type'] = 'application/json';
        opcoes.body = JSON.stringify(dados);
    }

    // Só pedidos que alteram dados precisam do token
    if (metodo !== 'GET') {
        cabecalhos['X-CSRF-Token'] = await obterToken();
    }

    const resposta = await fetch('api.php' + caminho, opcoes);
    const corpo = await resposta.json();

    return { status: resposta.status, corpo };
}

// Devolve { status, corpo }. Se o token estiver velho (ex.: logo após login),
// busca um novo e tenta uma vez mais.
async function api(caminho, metodo = 'GET', dados = null) {
    let resultado = await enviar(caminho, metodo, dados);

    const tokenRecusado =
        resultado.status === 403 &&
        typeof resultado.corpo.mensagem === 'string' &&
        resultado.corpo.mensagem.startsWith('Token CSRF');

    if (tokenRecusado) {
        await obterToken(true);
        resultado = await enviar(caminho, metodo, dados);
    }

    return resultado;
}