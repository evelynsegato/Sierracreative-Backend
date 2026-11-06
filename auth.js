// ---------- Elementos da página ----------
const formLogin = document.getElementById('form-login');
const formCadastro = document.getElementById('form-cadastro');
const abaLogin = document.getElementById('aba-login');
const abaCadastro = document.getElementById('aba-cadastro');
const mensagem = document.getElementById('auth-mensagem');

// ---------- Mostrar mensagens ----------
// Usamos textContent (e nunca innerHTML) para o texto nunca ser tratado como HTML.
// Isso protege contra XSS.
function mostrarMensagem(texto, tipo) {
    mensagem.textContent = texto;
    mensagem.className = 'auth-mensagem ' + tipo;
}

function limparErros(form) {
    form.querySelectorAll('[data-erro]').forEach((p) => {
        p.textContent = '';
    });
}

function mostrarErrosDosCampos(form, erros) {
    Object.entries(erros || {}).forEach(([campo, texto]) => {
        const p = form.querySelector('[data-erro="' + campo + '"]');
        if (p) {
            p.textContent = texto;
        }
    });
}

// ---------- Alternar entre Entrar e Criar conta ----------
function mostrarAba(qual) {
    const login = qual === 'login';

    formLogin.hidden = !login;
    formCadastro.hidden = login;
    abaLogin.classList.toggle('ativa', login);
    abaCadastro.classList.toggle('ativa', !login);
    mensagem.textContent = '';
}

abaLogin.addEventListener('click', () => mostrarAba('login'));
abaCadastro.addEventListener('click', () => mostrarAba('cadastro'));

// ---------- Login ----------
function fazerLogin(email, senha) {
    return api('/auth/login', 'POST', { email, senha });
}

formLogin.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    limparErros(formLogin);

    try {
        const { status, corpo } = await fazerLogin(
            formLogin.email.value,
            formLogin.senha.value
        );

        if (status === 200) {
            mostrarMensagem('Login realizado! Redirecionando...', 'ok');
            window.location.href = 'contato.html';
            return;
        }

        mostrarMensagem(corpo.mensagem || 'Não foi possível entrar.', 'erro');
        mostrarErrosDosCampos(formLogin, corpo.erros);
    } catch (erro) {
        mostrarMensagem('Erro de conexão com o servidor.', 'erro');
    }
});

// ---------- Cadastro ----------
formCadastro.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    limparErros(formCadastro);

    const dados = {
        nome: formCadastro.nome.value,
        email: formCadastro.email.value,
        senha: formCadastro.senha.value,
    };

    try {
        const { status, corpo } = await api('/auth/cadastro', 'POST', dados);

        if (status !== 201) {
            mostrarMensagem(corpo.mensagem || 'Não foi possível cadastrar.', 'erro');
            mostrarErrosDosCampos(formCadastro, corpo.erros);
            return;
        }

        // Cadastro deu certo: já faz o login automaticamente
        const login = await fazerLogin(dados.email, dados.senha);

        if (login.status === 200) {
            mostrarMensagem('Conta criada! Redirecionando...', 'ok');
            window.location.href = 'contato.html';
        } else {
            mostrarAba('login');
            mostrarMensagem('Conta criada! Agora é só entrar.', 'ok');
        }
    } catch (erro) {
        mostrarMensagem('Erro de conexão com o servidor.', 'erro');
    }
});