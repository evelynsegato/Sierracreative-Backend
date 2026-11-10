# Sierra Creative - Backend (Trabalho Individual II - SCOM)

Site da empresa Sierra Creative, produtora audiovisual (serviços de Videomaker e Storymaker).
Este trabalho é a continuação do Trabalho 1 para um sistema cliente-servidor, com API em PHP,
banco MySQL/MariaDB, autenticação por sessão, controle de acesso por perfil, proteções de segurança,
logs e testes automatizados.

- Repositório: https://github.com/evelynsegato/Sierracreative-Backend

## O que o sistema faz

- O visitante navega pelo site público (início, sobre, serviços, portfólio, contato).
- Qualquer pessoa pode criar uma conta (sempre com perfil `cliente`) e fazer login.
- O cliente logado envia solicitações de orçamento pelo formulário de contato e acompanha, edita e cancela as suas solicitações.
- O administrador vê todas as solicitações, muda o status e exclui.

## Tecnologias

- PHP 8 (sem frameworks), PDO com prepared statements
- MySQL/MariaDB
- HTML, CSS e JavaScript puro no front-end
- Servidor: Apache (XAMPP)

## Como executar

### 1. Requisitos
- XAMPP com **Apache** e **MySQL** (PHP 8.0 ou superior)

### 2. Colocar o projeto no lugar certo
Copie a pasta do projeto para `C:\xampp\htdocs\sierra-creative`.
O nome da pasta precisa ser exatamente `sierra-creative`, pois é usado nas URLs.

### 3. Vídeos do portfólio
Os vídeos não estão no repositório por causa do tamanho. O sistema funciona sem eles
(só o portfólio fica sem vídeos). Para exibi-los, coloque os arquivos em `public/videos/`.

### 4. Configurar o `.env`
Na raiz do projeto, copie `.env.example` para `.env`:

```
APP_ENV=development
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=sierra_creative
DB_USER=root
DB_PASS=
```

### 5. Criar o banco
1. Inicie **Apache** e **MySQL** no painel do XAMPP.
2. Abra `http://localhost/phpmyadmin`.
3. Aba **SQL**: cole o conteúdo de `database/schema.sql` e clique em **Executar**.

### 6. Abrir o site
`http://localhost/sierra-creative/public/index.html`

### 7. Criar o usuário administrador
Ninguém vira admin pelo formulário (segurança). Para criar o admin:

1. Em `entrar.html`, aba **Criar conta**, cadastre `admin@sierra.com` com a senha `admin12345`.
2. No phpMyAdmin, aba **SQL** do banco `sierra_creative`, execute:

```sql
UPDATE usuarios SET perfil = 'admin' WHERE email = 'admin@sierra.com';
```

### Contas de teste

| Perfil | E-mail | Senha |
| Administrador | admin@sierra.com | admin12345 |
| Cliente | crie pela aba "Criar conta" | mínimo 8 caracteres |

São contas apenas para avaliação em ambiente local.

## Testes automatizados

Com o MySQL ligado, na raiz do projeto:

```
C:\xampp\php\php.exe tests\rodar.php
```

São 9 testes (casos válidos e inválidos). Resultado esperado: `Total: 9 | Passaram: 9 | Falharam: 0`.
Os testes criam usuários temporários e os removem ao final.

## Estrutura do projeto

```
sierra-creative/
├── public/          Interface (HTML, CSS, JS) e ponto de entrada da API (api.php)
├── src/
│   ├── Controllers/ Recebem a requisição e devolvem a resposta
│   ├── Services/    Regras de negócio e validação
│   ├── Repositories/ Acesso ao banco (SQL)
│   └── Core/        Config, Database, Rotas, Sessao, Csrf, Autorizacao, Logger, Resposta
├── config/          Leitura do .env
├── database/        schema.sql
├── logs/            app.log (gerado automaticamente)
├── tests/           Testes automatizados
├── docs/            ARQUITETURA.md e API.md
├── .env.example     Modelo de configuração
└── README.md
```

## Segurança implementada

- Senhas com `password_hash` (bcrypt) e `password_verify`
- Sessão com cookie `HttpOnly` e `SameSite=Lax`, renovada no login
- Prepared statements em todas as consultas (SQL Injection)
- Escape de saída no front-end com `textContent` (XSS)
- Token CSRF obrigatório em POST, PUT, PATCH e DELETE
- Controle de acesso por perfil (401 sem login, 403 sem permissão)
- Validação e sanitização de toda entrada no backend
- Erros padronizados em JSON; detalhes técnicos só com `APP_ENV=development`
- Logs com data e hora (login, falha de login, criações, erro interno)

## Documentação

- API: [docs/API.md](docs/API.md)
- Arquitetura: [docs/ARQUITETURA.md](docs/ARQUITETURA.md)

## Observação sobre IA generativa

Este projeto foi desenvolvido com apoio de IA generativa (Claude, da Anthropic).
O uso está detalhado na seção 13 do relatório técnico.