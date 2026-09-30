# 📱 Tchat API - Backend (WhatsApp Clone)

Bem-vindo ao repositório do **Tchat API**, uma RESTful API construída com [CodeIgniter 4](https://codeigniter.com/) e PostgreSQL. Esta API foi desenvolvida para servir de backend para um aplicativo móvel estilo WhatsApp, suportando envio de mensagens, criação de conversas, autenticação via JWT e integração com webhooks de Push Notifications.

---

## 🚀 Tecnologias Utilizadas
- **PHP 8.2+**
- **CodeIgniter 4** (Framework PHP)
- **PostgreSQL** (Banco de Dados Relacional)
- **JWT (Firebase/php-jwt)** (Autenticação)
- **Composer** (Gerenciador de Dependências)

---

## ⚙️ Pré-requisitos
Antes de começar, verifique se você possui os seguintes itens instalados em sua máquina:
- [PHP 8.2+](https://windows.php.net/download/) (Certifique-se de que as extensões `intl`, `mbstring` e `pdo_pgsql` estão ativadas no seu `php.ini`)
- [Composer](https://getcomposer.org/)
- [PostgreSQL](https://www.postgresql.org/download/)

---

## 🛠️ Como Instalar e Rodar Localmente

Siga os passos abaixo para configurar o ambiente e rodar o projeto no seu computador:

### 1. Clonar o Repositório
Se ainda não clonou o repositório, faça isso:
```bash
git clone https://github.com/seu-usuario/tchat-api.git
cd tchat-api
```

### 2. Instalar as Dependências
Execute o comando abaixo na raiz do projeto para baixar os pacotes necessários pelo Composer:
```bash
composer install
```

### 3. Configurar o Banco de Dados (PostgreSQL)
1. Abra seu gerenciador do PostgreSQL (pgAdmin, DBeaver, ou psql) e **crie um banco de dados vazio** (ex: `tchat_db`).
2. Na raiz do projeto, existe um arquivo chamado `database.sql`. Execute todo o conteúdo desse script SQL dentro do banco de dados que você acabou de criar. Ele irá gerar todas as tabelas necessárias (`users`, `tchat_conversa`, `tchat_mensagem`, etc.).

### 4. Configurar as Variáveis de Ambiente (.env)
1. Na raiz do projeto, você notará um arquivo chamado `.env`. (Se não houver, copie o arquivo `env` original com o nome `.env`).
2. Abra o `.env` e ajuste as seguintes linhas para conectar ao seu banco:
```ini
database.default.hostname = localhost
database.default.database = tchat_db
database.default.username = postgres
database.default.password = sua_senha_do_postgres
database.default.DBDriver = Postgre

# Adicione também sua chave secreta para os Tokens JWT
JWT_SECRET_KEY = 'coloque_uma_chave_bem_segura_aqui_para_o_jwt'
JWT_TIME_TO_LIVE = 604800 # 7 dias
```

### 5. Iniciar o Servidor Local
Agora que tudo está configurado, inicie o servidor embutido do CodeIgniter:
```bash
php spark serve
```
A API estará rodando em: `http://localhost:8080/`

---

## 📱 Testando a API
Você pode usar o [Postman](https://www.postman.com/) ou [Insomnia](https://insomnia.rest/) para testar as rotas.

**Principais Endpoints:**
- `POST /api/register`: Cria um novo usuário (payload: `name`, `phone`, `password`).
- `POST /api/login`: Faz o login e retorna o Token JWT (payload: `phone`, `password`).
- `GET /api/conversas`: Lista suas conversas *(Requer header `Authorization: Bearer <SEU_TOKEN>`)*.
- `POST /api/conversas/privada`: Cria uma conversa com outro usuário (payload: `target_user_id`).
- `POST /api/mensagens`: Envia uma mensagem para o chat (payload: `id_conversa`, `mensagem`).

---

## 🔔 Webhook de Push Notifications
O endpoint `POST /api/push/webhook` está preparado para receber eventos ("receipts") de serviços de disparo (como o Expo Notifications). Ele analisa se algum token de dispositivo tornou-se inválido e, caso positivo, remove automaticamente do banco de dados, mantendo a tabela de push limpa.

> *Projeto desenvolvido para Portfólio.*
