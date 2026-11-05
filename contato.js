const formSolicitacao = document.getElementById('form-solicitacao');
const avisoLogin = document.getElementById('aviso-login');
const botaoEnviar = document.getElementById('botao-enviar');
const msgFormulario = document.getElementById('mensagem-formulario');

function mostrarMsg(texto, tipo) {
    // textContent: o texto nunca é interpretado como HTML (proteção XSS)
    msgFormulario.textContent = texto;
    msgFormulario.className = 'auth-mensagem ' + tipo;
}

function limparErrosForm() {
    formSolicitacao.querySelectorAll('[data-erro]').forEach((p) => {
        p.textContent = '';
    });
}

// Opção B: sem login, o formulário continua visível mas desativado
function bloquearFormulario(bloquear) {
    avisoLogin.hidden = !bloquear;

    formSolicitacao.querySelectorAll('input, select, textarea, button').forEach((campo) => {
        campo.disabled = bloquear;
    });
}

async function verificarLogin() {
    try {
        const resposta = await fetch('api.php/auth/eu');
        bloquearFormulario(resposta.status !== 200);
    } catch (erro) {
        bloquearFormulario(true);
    }
}

formSolicitacao.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    limparErrosForm();
    mostrarMsg('', '');

    const dados = {
        tipo_servico: formSolicitacao.tipo_servico.value,
        telefone: formSolicitacao.telefone.value,
        mensagem: formSolicitacao.mensagem.value,
    };

    try {
        const resposta = await fetch('api.php/solicitacoes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados),
        });

        const corpo = await resposta.json();

        if (resposta.status === 201) {
            formSolicitacao.reset();
            mostrarMsg('Mensagem enviada com sucesso! Entraremos em contato.', 'ok');
            return;
        }

        if (resposta.status === 401) {
            bloquearFormulario(true);
            mostrarMsg('Sua sessão expirou. Faça login novamente.', 'erro');
            return;
        }

        mostrarMsg(corpo.mensagem || 'Não foi possível enviar.', 'erro');

        Object.entries(corpo.erros || {}).forEach(([campo, texto]) => {
            const p = formSolicitacao.querySelector('[data-erro="' + campo + '"]');
            if (p) {
                p.textContent = texto;
            }
        });
    } catch (erro) {
        mostrarMsg('Erro de conexão com o servidor.', 'erro');
    }
});

verificarLogin();