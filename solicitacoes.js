const lista = document.getElementById('lista-solicitacoes');
const msg = document.getElementById('painel-mensagem');
const formEdicao = document.getElementById('form-edicao');
const titulo = document.getElementById('painel-titulo');
const rotuloPerfil = document.getElementById('painel-perfil');

const NOMES_TIPO = { videomaker: 'Videomaker', storymaker: 'Storymaker' };
const NOMES_STATUS = {
    pendente: 'Pendente',
    em_andamento: 'Em andamento',
    concluida: 'Concluída',
    cancelada: 'Cancelada',
};

let usuario = null;
let solicitacoes = [];
let idEditando = null;

// ---------- Utilidades ----------
function mostrar(texto, tipo) {
    // textContent: nunca interpreta HTML (proteção XSS)
    msg.textContent = texto;
    msg.className = 'auth-mensagem ' + (tipo || '');
}

function criar(tag, texto, classe) {
    const el = document.createElement(tag);
    if (texto !== undefined) el.textContent = texto;
    if (classe) el.className = classe;
    return el;
}

function botao(texto, aoClicar, classe) {
    const b = criar('button', texto, classe);
    b.type = 'button';
    b.addEventListener('click', aoClicar);
    return b;
}

function formatarData(texto) {
    const d = new Date(texto.replace(' ', 'T'));
    return isNaN(d) ? texto : d.toLocaleDateString('pt-BR');
}

// ---------- Desenhar a lista ----------
// Todo texto vindo do servidor entra por textContent, nunca innerHTML.
function desenhar() {
    lista.replaceChildren();

    if (solicitacoes.length === 0) {
        lista.appendChild(criar('p', 'Nenhuma solicitação encontrada.'));
        return;
    }

    solicitacoes.forEach((s) => lista.appendChild(montarCartao(s)));
}

function montarCartao(s) {
    const admin = usuario.perfil === 'admin';
    const cartao = criar('div', undefined, 'cartao');

    const topo = criar('div', undefined, 'cartao-topo');
    topo.appendChild(criar('h3', '#' + s.id + ' · ' + (NOMES_TIPO[s.tipo_servico] || s.tipo_servico)));
    topo.appendChild(criar('span', NOMES_STATUS[s.status] || s.status, 'etiqueta ' + s.status));
    cartao.appendChild(topo);

    if (admin && s.cliente) {
        cartao.appendChild(criar('p', 'Cliente: ' + s.cliente));
    }

    cartao.appendChild(criar('p', 'Telefone: ' + s.telefone));
    cartao.appendChild(criar('p', s.mensagem));
    cartao.appendChild(criar('p', 'Enviada em ' + formatarData(s.criado_em)));

    const acoes = criar('div', undefined, 'painel-botoes');

    if (admin) {
        const seletor = criar('select');
        Object.entries(NOMES_STATUS).forEach(([valor, nome]) => {
            const op = criar('option', nome);
            op.value = valor;
            op.selected = valor === s.status;
            seletor.appendChild(op);
        });
        seletor.addEventListener('change', () => mudarStatus(s.id, seletor.value));
        acoes.appendChild(seletor);
        acoes.appendChild(botao('Editar', () => abrirEdicao(s)));
        acoes.appendChild(botao('Excluir', () => excluir(s.id), 'perigo'));
    } else if (s.status === 'pendente') {
        acoes.appendChild(botao('Editar', () => abrirEdicao(s)));
        acoes.appendChild(botao('Cancelar', () => cancelar(s.id), 'perigo'));
    }

    if (acoes.children.length > 0) {
        cartao.appendChild(acoes);
    }

    return cartao;
}

// ---------- Ações ----------
async function carregar() {
    const { status, corpo } = await api('/solicitacoes');

    if (status !== 200) {
        mostrar(corpo.mensagem || 'Não foi possível carregar.', 'erro');
        return;
    }

    solicitacoes = corpo.dados;
    desenhar();
}

async function executar(promessa, sucesso) {
    const { status, corpo } = await promessa;

    if (status === 200) {
        mostrar(sucesso, 'ok');
        await carregar();
        return true;
    }

    mostrar(corpo.mensagem || 'Ação não realizada.', 'erro');
    return false;
}

function mudarStatus(id, novo) {
    return executar(api('/solicitacoes/' + id + '/status', 'PATCH', { status: novo }), 'Status atualizado.');
}

function cancelar(id) {
    if (!confirm('Cancelar esta solicitação?')) return;
    return executar(api('/solicitacoes/' + id + '/cancelar', 'POST'), 'Solicitação cancelada.');
}

function excluir(id) {
    if (!confirm('Excluir esta solicitação definitivamente?')) return;
    return executar(api('/solicitacoes/' + id, 'DELETE'), 'Solicitação excluída.');
}

// ---------- Edição ----------
function abrirEdicao(s) {
    idEditando = s.id;
    formEdicao.tipo_servico.value = s.tipo_servico;
    formEdicao.telefone.value = s.telefone;
    formEdicao.mensagem.value = s.mensagem;
    limparErros();
    formEdicao.hidden = false;
    formEdicao.scrollIntoView({ behavior: 'smooth' });
}

function limparErros() {
    formEdicao.querySelectorAll('[data-erro]').forEach((p) => (p.textContent = ''));
}

document.getElementById('edit-cancelar').addEventListener('click', () => {
    formEdicao.hidden = true;
    idEditando = null;
});

formEdicao.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    limparErros();

    const dados = {
        tipo_servico: formEdicao.tipo_servico.value,
        telefone: formEdicao.telefone.value,
        mensagem: formEdicao.mensagem.value,
    };

    const { status, corpo } = await api('/solicitacoes/' + idEditando, 'PUT', dados);

    if (status === 200) {
        formEdicao.hidden = true;
        idEditando = null;
        mostrar('Solicitação atualizada.', 'ok');
        await carregar();
        return;
    }

    mostrar(corpo.mensagem || 'Não foi possível salvar.', 'erro');

    Object.entries(corpo.erros || {}).forEach(([campo, texto]) => {
        const p = formEdicao.querySelector('[data-erro="' + campo + '"]');
        if (p) p.textContent = texto;
    });
});

// ---------- Início ----------
(async function iniciar() {
    try {
        const eu = await api('/auth/eu');

        if (eu.status !== 200) {
            window.location.href = 'entrar.html';
            return;
        }

        usuario = eu.corpo.dados;

        if (usuario.perfil === 'admin') {
            rotuloPerfil.textContent = 'PAINEL DO ADMINISTRADOR';
            titulo.textContent = 'Todas as solicitações';
        }

        await carregar();
    } catch (erro) {
        mostrar('Erro de conexão com o servidor.', 'erro');
    }
})();