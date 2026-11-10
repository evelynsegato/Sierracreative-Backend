# Documentação da API - Sierra Creative

## Informações gerais

- **URL base:** `http://localhost/sierra-creative/public/api.php`
- **Formato:** JSON (`Content-Type: application/json`)
- **Autenticação:** sessão por cookie (criada no login)
- **CSRF:** toda requisição `POST`, `PUT`, `PATCH` e `DELETE` exige o cabeçalho `X-CSRF-Token`. O token é obtido em `GET /auth/csrf`. O token muda a cada login.

### Formato das respostas

Sucesso:
```json
{"sucesso": true, "dados": {}}
```

Erro:
```json
{"sucesso": false, "mensagem": "Texto legível", "erros": {"campo": "problema"}}
```

O campo `erros` aparece apenas em validações (422). Em erros 500, o campo `erros.tecnico` só aparece com `APP_ENV=development`.

### Códigos de status usados

| Código | Significado |
|---|---|
| 200 | Sucesso |
| 201 | Criado com sucesso |
| 400 | Corpo da requisição não é JSON válido |
| 401 | Não autenticado (sem login ou credenciais incorretas) |
| 403 | Sem permissão (perfil insuficiente, não é o dono, ou token CSRF inválido) |
| 404 | Rota ou recurso não encontrado |
| 405 | Método não permitido para a rota |
| 409 | Conflito de estado (ex.: editar solicitação que não está pendente) |
| 422 | Dados inválidos (validação) |
| 500 | Erro interno |

---

## Autenticação

### GET /auth/csrf

Retorna o token CSRF da sessão atual. Pode ser chamado sem login.

- **Parâmetros:** nenhum
- **Resposta 200:**
```json
{"sucesso": true, "dados": {"token": "9f2c...64 caracteres"}}
```

---

### POST /auth/cadastro

Cria uma conta. O perfil criado é sempre `cliente`.

- **Cabeçalho:** `X-CSRF-Token`
- **Corpo:**

| Campo | Tipo | Regras |
|---|---|---|
| nome | string | 2 a 100 caracteres |
| email | string | e-mail válido, até 150 caracteres, único |
| senha | string | 8 a 72 caracteres |

- **Exemplo de requisição:**
```json
{"nome": "Maria Silva", "email": "maria@email.com", "senha": "senha12345"}
```
- **Resposta 201:**
```json
{"sucesso": true, "dados": {"id": 2}}
```
- **Resposta 422:**
```json
{"sucesso": false, "mensagem": "Dados inválidos.", "erros": {"senha": "A senha deve ter entre 8 e 72 caracteres."}}
```
- **Status possíveis:** 201, 400, 403 (CSRF), 422

---

### POST /auth/login

Autentica e abre a sessão.

- **Cabeçalho:** `X-CSRF-Token`
- **Corpo:** `email` (string), `senha` (string)
- **Exemplo de requisição:**
```json
{"email": "maria@email.com", "senha": "senha12345"}
```
- **Resposta 200:**
```json
{"sucesso": true, "dados": {"nome": "Maria Silva", "perfil": "cliente"}}
```
- **Resposta 401:**
```json
{"sucesso": false, "mensagem": "E-mail ou senha incorretos."}
```
- **Status possíveis:** 200, 400, 401, 403 (CSRF)

---

### POST /auth/logout

Encerra a sessão.

- **Cabeçalho:** `X-CSRF-Token`
- **Parâmetros:** nenhum
- **Resposta 200:**
```json
{"sucesso": true, "dados": {"mensagem": "Sessão encerrada."}}
```
- **Status possíveis:** 200, 403 (CSRF)

---

### GET /auth/eu

Retorna o usuário logado. Rota protegida.

- **Parâmetros:** nenhum
- **Resposta 200:**
```json
{"sucesso": true, "dados": {"id": 2, "nome": "Maria Silva", "perfil": "cliente"}}
```
- **Resposta 401:**
```json
{"sucesso": false, "mensagem": "Você precisa estar logado."}
```
- **Status possíveis:** 200, 401

---

## Solicitações

Todas as rotas abaixo exigem login.

Campos de uma solicitação:

| Campo | Regras |
|---|---|
| telefone | 10 ou 11 números (símbolos são removidos) |
| tipo_servico | `videomaker` ou `storymaker` |
| mensagem | 10 a 2000 caracteres |

Valores de `status`: `pendente`, `em_andamento`, `concluida`, `cancelada`.

### POST /solicitacoes

Cria uma solicitação para o usuário logado. Perfis: cliente e administrador.

- **Cabeçalho:** `X-CSRF-Token`
- **Exemplo de requisição:**
```json
{"telefone": "(15) 99800-7700", "tipo_servico": "videomaker", "mensagem": "Quero um video do meu aniversario"}
```
- **Resposta 201:**
```json
{"sucesso": true, "dados": {"id": 1}}
```
- **Resposta 422:**
```json
{"sucesso": false, "mensagem": "Dados inválidos.", "erros": {"telefone": "O telefone deve ter 10 ou 11 números (com DDD).", "tipo_servico": "Escolha videomaker ou storymaker.", "mensagem": "A mensagem deve ter entre 10 e 2000 caracteres."}}
```
- **Status possíveis:** 201, 400, 401, 403 (CSRF), 422

---

### GET /solicitacoes

Lista solicitações. Cliente recebe só as suas; administrador recebe todas (com o campo `cliente`).

- **Parâmetros:** nenhum
- **Resposta 200 (cliente):**
```json
{"sucesso": true, "dados": [
  {"id": 1, "usuario_id": 2, "telefone": "15998007700", "tipo_servico": "videomaker",
   "mensagem": "Quero um video do meu aniversario", "status": "pendente", "criado_em": "2026-11-06 14:40:03"}
]}
```
- **Status possíveis:** 200, 401

---

### GET /solicitacoes/{id}

Retorna uma solicitação.

- **Parâmetro de rota:** `id` (inteiro)
- **Resposta 200:** objeto da solicitação (mesmos campos acima).
- **Resposta 403:**
```json
{"sucesso": false, "mensagem": "Você não tem permissão para ver esta solicitação."}
```
- **Status possíveis:** 200, 401, 403 (cliente tentando ver a de outro), 404

---

### PUT /solicitacoes/{id}

Edita telefone, tipo e mensagem. Cliente: só a sua e só se `pendente`. Administrador: qualquer.

- **Cabeçalho:** `X-CSRF-Token`
- **Exemplo de requisição:**
```json
{"telefone": "15998007700", "tipo_servico": "storymaker", "mensagem": "Mensagem editada pela cliente"}
```
- **Resposta 200:**
```json
{"sucesso": true, "dados": {"mensagem": "Solicitação atualizada."}}
```
- **Resposta 409:**
```json
{"sucesso": false, "mensagem": "Só é possível alterar solicitações pendentes."}
```
- **Status possíveis:** 200, 400, 401, 403 (não é o dono ou CSRF), 404, 409, 422

---

### POST /solicitacoes/{id}/cancelar

Muda o status para `cancelada`. Mesmas regras de dono e estado do `PUT`.

- **Cabeçalho:** `X-CSRF-Token`
- **Corpo:** nenhum
- **Resposta 200:**
```json
{"sucesso": true, "dados": {"mensagem": "Solicitação cancelada."}}
```
- **Status possíveis:** 200, 401, 403, 404, 409

---

### PATCH /solicitacoes/{id}/status

Altera o status. **Somente administrador.**

- **Cabeçalho:** `X-CSRF-Token`
- **Exemplo de requisição:**
```json
{"status": "em_andamento"}
```
- **Resposta 200:**
```json
{"sucesso": true, "dados": {"mensagem": "Status atualizado."}}
```
- **Resposta 403 (cliente):**
```json
{"sucesso": false, "mensagem": "Você não tem permissão para esta ação."}
```
- **Status possíveis:** 200, 400, 401, 403, 404, 422 (status desconhecido)

---

### DELETE /solicitacoes/{id}

Exclui definitivamente. **Somente administrador.**

- **Cabeçalho:** `X-CSRF-Token`
- **Resposta 200:**
```json
{"sucesso": true, "dados": {"mensagem": "Solicitação excluída."}}
```
- **Status possíveis:** 200, 401, 403, 404

---

## Rotas inexistentes

- Caminho que não existe: **404** `{"sucesso": false, "mensagem": "Rota não encontrada."}`
- Caminho existente com método errado: **405** `{"sucesso": false, "mensagem": "Método não permitido para esta rota."}`

## Exemplo de uso completo (Console do navegador)

```js
// 1. Obter o token
const t = (await (await fetch('/sierra-creative/public/api.php/auth/csrf')).json()).dados.token;

// 2. Login
await fetch('/sierra-creative/public/api.php/auth/login', {
  method: 'POST',
  headers: {'Content-Type': 'application/json', 'X-CSRF-Token': t},
  body: JSON.stringify({email: 'maria@email.com', senha: 'senha12345'})
});

// 3. Como o login troca o token, buscar o novo antes de criar
const t2 = (await (await fetch('/sierra-creative/public/api.php/auth/csrf')).json()).dados.token;

// 4. Criar solicitação
const r = await fetch('/sierra-creative/public/api.php/solicitacoes', {
  method: 'POST',
  headers: {'Content-Type': 'application/json', 'X-CSRF-Token': t2},
  body: JSON.stringify({telefone: '15998007700', tipo_servico: 'videomaker', mensagem: 'Quero um video de aniversario'})
});
console.log(await r.json());
```
