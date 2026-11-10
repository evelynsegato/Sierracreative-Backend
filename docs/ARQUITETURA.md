# Arquitetura do Sistema - Sierra Creative

## 1. Visão geral

O sistema segue o modelo **cliente-servidor** com **arquitetura em camadas**.

- **Cliente (interface):** páginas HTML, CSS e JavaScript em `public/`. O JavaScript conversa com o servidor por requisições `fetch` e recebe JSON.
- **Servidor (lógica de aplicação):** PHP em `src/`, exposto por um único ponto de entrada, `public/api.php`.
- **Dados:** MySQL/MariaDB, acessado somente pela camada de repositórios.

## 2. Camadas e responsabilidades

```
Navegador (HTML/CSS/JS)
        |
        |  fetch (JSON)
        v
public/api.php  ----->  Rotas (Core)
        |
        v
Controllers   recebem a requisição, checam permissão, devolvem a resposta
        |
        v
Services      regras de negócio, validação e sanitização
        |
        v
Repositories  único lugar com SQL (prepared statements)
        |
        v
Banco de dados (MySQL/MariaDB)
```

| Camada | Pasta | Responsabilidade | Não faz |
|---|---|---|---|
| Interface | `public/` | Telas e chamadas à API | Não valida como segurança, não acessa banco |
| Controllers | `src/Controllers/` | Lê o JSON, aplica autorização, traduz o resultado em resposta HTTP | Não contém regra de negócio nem SQL |
| Services | `src/Services/` | Valida tipos e tamanhos, sanitiza, aplica regras (dono, status) | Não conhece HTTP nem SQL |
| Repositories | `src/Repositories/` | Executa SQL com prepared statements | Não toma decisões de negócio |
| Core | `src/Core/` | Peças de base usadas por todos | - |

### Peças do Core

| Classe | Função |
|---|---|
| `Config` | Entrega valores do `.env` (lido por `config/config.php`) |
| `Database` | Abre a conexão PDO (exceções ligadas, prepares reais) |
| `Rotas` | Encaminha método + caminho para a ação certa (404 e 405 automáticos) |
| `Sessao` | Sessão segura (cookie HttpOnly, SameSite=Lax, id renovado no login) |
| `Csrf` | Gera e verifica o token CSRF |
| `Autorizacao` | Exige login (401) e perfil (403) |
| `Resposta` | Padroniza as respostas JSON de sucesso e erro |
| `Logger` | Registra eventos com data e hora em `logs/app.log` |

O carregamento das classes é automático (`spl_autoload_register` em `src/bootstrap.php`).

## 3. Fluxo de uma requisição

Exemplo: o cliente cria uma solicitação (`POST /api.php/solicitacoes`).

1. O JavaScript (`api.js`) obtém o token CSRF e envia o pedido com o cabeçalho `X-CSRF-Token`.
2. `api.php` carrega o `bootstrap.php` (autoload, fuso horário, sessão).
3. `Csrf::verificar()` confere o token. Se faltar ou for inválido: **403**.
4. `Rotas` encontra a ação `SolicitacaoController::criar`.
5. O Controller chama `Autorizacao::exigirPerfil('cliente', 'admin')`. Sem login: **401**.
6. O Controller entrega os dados ao `SolicitacaoService`, que valida e sanitiza. Dados inválidos: **422** com a lista de erros.
7. O Service chama o `SolicitacaoRepository`, que executa o `INSERT` com prepared statement.
8. O Controller registra `solicitacao_criada` no log e responde **201** com o `id`.

## 4. Tratamento centralizado de erros

Todo o roteamento ocorre dentro de um `try/catch` em `api.php`. Qualquer exceção não prevista:

- é registrada no log com o detalhe técnico (`erro_interno`);
- devolve ao usuário apenas "Erro interno do servidor" (**500**);
- inclui o detalhe técnico na resposta somente se `APP_ENV=development`.

Formato único das respostas:

```json
{"sucesso": true, "dados": {}}
{"sucesso": false, "mensagem": "Texto legível", "erros": {"campo": "problema"}}
```

## 5. Modelo de dados

```
usuarios (1) ----< (N) solicitacoes
```

| Tabela | Campos principais | Restrições |
|---|---|---|
| `usuarios` | id, nome, email, senha_hash, perfil, criado_em | PK (id), UNIQUE (email), NOT NULL, perfil em ('admin','cliente') |
| `solicitacoes` | id, usuario_id, telefone, tipo_servico, mensagem, status, criado_em, atualizado_em | PK (id), FK usuario_id -> usuarios(id) ON DELETE CASCADE, NOT NULL, ENUMs de tipo e status |

Índices: `idx_solicitacoes_usuario` (usuario_id) e `idx_solicitacoes_status` (status), para acelerar a listagem por dono e por status.

## 6. Perfis e matriz de permissões

Perfis: **visitante** (sem login), **cliente** e **administrador**.

| Ação | Visitante | Cliente | Administrador |
|---|---|---|---|
| Ver páginas públicas | Sim | Sim | Sim |
| Criar conta e fazer login | Sim | Sim | Sim |
| Criar solicitação | Não (401) | Sim | Sim |
| Listar solicitações | Não (401) | Somente as suas | Todas |
| Ver uma solicitação | Não (401) | Somente as suas (403 nas de outros) | Qualquer |
| Editar solicitação | Não (401) | Somente a sua e só se `pendente` (409 se não) | Qualquer |
| Cancelar solicitação | Não (401) | Somente a sua e só se `pendente` | Qualquer |
| Alterar status | Não (401) | Não (403) | Sim |
| Excluir solicitação | Não (401) | Não (403) | Sim |

Observações:

- O cadastro sempre cria o perfil `cliente`. O perfil `admin` só é definido diretamente no banco.
- As permissões são garantidas **no backend** (`Autorizacao` e `SolicitacaoService`). Esconder botões no front-end é apenas conforto visual.

## 7. Decisões de segurança

| Ameaça | Defesa | Onde |
|---|---|---|
| SQL Injection | Prepared statements, sem concatenar entrada no SQL | `Repositories/` |
| XSS | Conteúdo do servidor inserido com `textContent`, nunca `innerHTML` | `public/js/` |
| CSRF | Token por sessão no cabeçalho `X-CSRF-Token`, comparado com `hash_equals` | `Core/Csrf.php` |
| Roubo de cookie | Cookie `HttpOnly` e `SameSite=Lax` | `Core/Sessao.php` |
| Session fixation | `session_regenerate_id` no login | `Core/Sessao.php` |
| Senhas expostas | `password_hash` (bcrypt), nunca a senha pura | `Services/AuthService.php` |
| Enumeração de e-mails | Mesma mensagem para e-mail inexistente e senha errada | `AuthService::autenticar` |
| Acesso indevido | 401 sem login, 403 sem permissão ou sem ser dono | `Core/Autorizacao.php` |
| Log injection | Quebras de linha removidas dos valores do log | `Core/Logger.php` |
| Vazamento de segredos | Configuração no `.env`, fora do repositório | `.env` |

## 8. Limitações conhecidas

- A validação está na camada de aplicação: quem acessa o banco diretamente (phpMyAdmin) a contorna. Em produção, o acesso ao banco deve ser restrito.
- O cookie de sessão não usa a flag `Secure`, pois o ambiente local roda em HTTP. Em produção, deve-se usar HTTPS e ativá-la.
- Não há limitação de tentativas de login (rate limiting).