// Adiciona ao menu: "Minhas solicitações"/"Painel" e "Sair" (logado) ou "Entrar" (visitante)
(async function () {
    const nav = document.querySelector('header nav');

    if (!nav) {
        return;
    }

    const link = document.createElement('a');
    link.href = 'entrar.html';
    link.textContent = 'Entrar';

    if (window.location.pathname.endsWith('entrar.html')) {
        link.classList.add('pagina-atual');
    }

    try {
        const resposta = await fetch('api.php/auth/eu');

        if (resposta.status === 200) {
            const corpo = await resposta.json();
            const primeiroNome = corpo.dados.nome.split(' ')[0];

            const painel = document.createElement('a');
            painel.href = 'solicitacoes.html';
            painel.textContent = corpo.dados.perfil === 'admin' ? 'Painel' : 'Minhas solicitações';

            if (window.location.pathname.endsWith('solicitacoes.html')) {
                painel.classList.add('pagina-atual');
            }

            nav.appendChild(painel);

            link.textContent = 'Sair (' + primeiroNome + ')';
            link.href = '#';

            link.addEventListener('click', async (evento) => {
                evento.preventDefault();

                // O logout altera dados, então precisa do token CSRF
                const respToken = await fetch('api.php/auth/csrf');
                const corpoToken = await respToken.json();

                await fetch('api.php/auth/logout', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': corpoToken.dados.token },
                });

                window.location.href = 'index.html';
            });
        }
    } catch (erro) {
        // Se a API não responder, mantém o "Entrar"
    }

    nav.appendChild(link);
})();