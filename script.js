const carrosseis = document.querySelectorAll('.carrossel');

carrosseis.forEach(carrossel => {
    let isDown = false;
    let startX;
    let scrollLeft;

    carrossel.addEventListener('mousedown', (e) => {
        isDown = true;
        carrossel.classList.add('ativo');
        startX = e.pageX - carrossel.offsetLeft;
        scrollLeft = carrossel.scrollLeft;
    });

    carrossel.addEventListener('mouseleave', () => {
        isDown = false;
        carrossel.classList.remove('ativo');
    });

    carrossel.addEventListener('mouseup', () => {
        isDown = false;
        carrossel.classList.remove('ativo');
    });

    carrossel.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - carrossel.offsetLeft;
        const walk = (x - startX) * 2;
        carrossel.scrollLeft = scrollLeft - walk;
    });
});

let servicoAtual = 0;

const slidesServico = document.querySelectorAll('.servico-slide');
const bolinhasServico = document.querySelectorAll('.bolinha-servico');

function mostrarServico(numero) {

    slidesServico.forEach(slide => {
        slide.classList.remove('ativo');
    });

    bolinhasServico.forEach(bolinha => {
        bolinha.classList.remove('ativa');
    });

    slidesServico[numero].classList.add('ativo');
    bolinhasServico[numero].classList.add('ativa');

    servicoAtual = numero;
}


function mudarServico(direcao) {

    servicoAtual += direcao;

    if (servicoAtual >= slidesServico.length) {
        servicoAtual = 0;
    }

    if (servicoAtual < 0) {
        servicoAtual = slidesServico.length - 1;
    }

    mostrarServico(servicoAtual);
}


function irParaServico(numero) {

    mostrarServico(numero);

}


if (slidesServico.length > 0) {

    setInterval(() => {

        mudarServico(1);

    }, 6000);

}


const telefoneInput = document.getElementById('telefone');
const erroTelefone = document.getElementById('erro-telefone');
const formulario = document.querySelector('.formulario-contato');

if (telefoneInput && erroTelefone && formulario) {

    telefoneInput.addEventListener('keyup', () => {

        const telefone = telefoneInput.value;

        if (telefone.length < 10) {

            erroTelefone.textContent = 'Digite um telefone válido.';

        } else {

            erroTelefone.textContent = '';

        }

    });

    formulario.addEventListener('submit', (event) => {

        event.preventDefault();

        if (telefoneInput.value.length >= 10) {

            alert('Mensagem enviada com sucesso!');

        } else {

            alert('Digite um telefone válido.');

        }

    });

}