-- ==============================================================================
-- 📱 Sistema Tchat Mobile - Banco de Dados
-- 💼 Preparado para Portfólio Profissional
-- ==============================================================================
-- Este script realiza a criação estruturada das tabelas necessárias para o 
-- funcionamento do clone do WhatsApp (Tchat Mobile), incluindo:
-- 1. Criação do Schema
-- 2. Constraints de integridade (Chaves Estrangeiras)
-- 3. Inserção de Dados de Teste (Mock Data) para apresentação do Portfólio
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. TABELA DE USUÁRIOS
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Senha com hash (Bcrypt)
    avatar_url VARCHAR(255) NULL,
    status_message VARCHAR(255) DEFAULT 'Hey there! I am using Tchat.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------------------------
-- 2. TABELA DE CONVERSAS (Salas / Grupos / Chats Diretos)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tchat_conversa (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(1) NOT NULL DEFAULT 'P', -- 'P': Privada (1 a 1), 'G': Grupo
    nome VARCHAR(150) NULL, -- Nome do Grupo (Nulo para chats 1 a 1)
    chave_privada VARCHAR(100) UNIQUE NULL, -- Previne duplicidade de chats 1 a 1
    imagem VARCHAR(255) NULL,
    ultima_mensagem_id INT NULL,
    ultima_atividade_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (criado_por) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------------------------
-- 3. TABELA DE PARTICIPANTES (NxN)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tchat_participante (
    id_conversa INT NOT NULL,
    id_usuario INT NOT NULL,
    ultima_mensagem_lida_id INT DEFAULT 0,
    entrou_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    saiu_em TIMESTAMP NULL,
    ativo BOOLEAN DEFAULT TRUE,
    administrador BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (id_conversa, id_usuario),
    FOREIGN KEY (id_conversa) REFERENCES tchat_conversa(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------------------------
-- 4. TABELA DE MENSAGENS
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tchat_mensagem (
    id SERIAL PRIMARY KEY,
    id_conversa INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo VARCHAR(20) DEFAULT 'texto', -- texto, imagem, audio
    mensagem TEXT NOT NULL,
    respondendo_id INT NULL, 
    client_id VARCHAR(80) NULL, -- Controle de idempotência mobile
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    editado_em TIMESTAMP NULL,
    excluido_em TIMESTAMP NULL,
    FOREIGN KEY (id_conversa) REFERENCES tchat_conversa(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (respondendo_id) REFERENCES tchat_mensagem(id) ON DELETE SET NULL
);

-- Evitar erro se a tabela for carregada múltiplas vezes
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.table_constraints 
        WHERE constraint_name = 'fk_ultima_mensagem'
    ) THEN
        ALTER TABLE tchat_conversa 
        ADD CONSTRAINT fk_ultima_mensagem 
        FOREIGN KEY (ultima_mensagem_id) REFERENCES tchat_mensagem(id) ON DELETE SET NULL;
    END IF;
END $$;

-- ------------------------------------------------------------------------------
-- 4.5 TABELA DE ANEXOS (Mídias, Imagens, Documentos)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tchat_anexo (
    id SERIAL PRIMARY KEY,
    id_mensagem INT NOT NULL,
    tipo VARCHAR(20) NOT NULL, -- 'imagem', 'pdf', 'audio', 'documento'
    nome_original VARCHAR(255) NOT NULL,
    caminho VARCHAR(255) NOT NULL, -- URL ou caminho local
    tamanho INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    excluido_em TIMESTAMP NULL,
    FOREIGN KEY (id_mensagem) REFERENCES tchat_mensagem(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------------------------
-- 5. TABELA DE ASSINATURAS PUSH (Webhooks/Notificações)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tchat_push_assinatura (
    id SERIAL PRIMARY KEY,
    id_usuario INT NOT NULL,
    device_token TEXT NOT NULL,
    plataforma VARCHAR(20) DEFAULT 'android',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(id_usuario, device_token),
    FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE CASCADE
);

-- ==============================================================================
-- 🚀 DADOS DE TESTE (Mock Data para Portfólio)
-- Senha padrão para todos: '123456' (Hash bcrypt: $2y$10$WqB... )
-- ==============================================================================

-- Inserindo Usuários
INSERT INTO users (name, phone, password, status_message, avatar_url) VALUES 
('João Silva', '+5511999999991', '$2y$10$T8Z6M3tPjD1eO8jE4U4NJeV/wF1yv/qXw3I/.B.sN71u0OQ9rGj6i', 'Desenvolvedor Full Stack 💻', 'https://i.pravatar.cc/150?u=joao'),
('Maria Oliveira', '+5511999999992', '$2y$10$T8Z6M3tPjD1eO8jE4U4NJeV/wF1yv/qXw3I/.B.sN71u0OQ9rGj6i', 'Disponível', 'https://i.pravatar.cc/150?u=maria'),
('Carlos Souza', '+5511999999993', '$2y$10$T8Z6M3tPjD1eO8jE4U4NJeV/wF1yv/qXw3I/.B.sN71u0OQ9rGj6i', 'Apenas mensagens urgentes.', 'https://i.pravatar.cc/150?u=carlos')
ON CONFLICT (phone) DO NOTHING;

-- Inserindo Conversa Privada (João e Maria)
INSERT INTO tchat_conversa (id, tipo, chave_privada, criado_por) 
VALUES (1, 'P', '1_2', 1) ON CONFLICT (id) DO NOTHING;

-- Vinculando Participantes à Conversa
INSERT INTO tchat_participante (id_conversa, id_usuario) VALUES (1, 1) ON CONFLICT DO NOTHING;
INSERT INTO tchat_participante (id_conversa, id_usuario) VALUES (1, 2) ON CONFLICT DO NOTHING;

-- Inserindo Mensagens Iniciais
INSERT INTO tchat_mensagem (id, id_conversa, id_usuario, mensagem) 
VALUES (1, 1, 1, 'Oi Maria! Tudo bem? Este é o nosso primeiro chat no app.') ON CONFLICT DO NOTHING;

INSERT INTO tchat_mensagem (id, id_conversa, id_usuario, mensagem) 
VALUES (2, 1, 2, 'Olá João! Ficou incrível o sistema. 🚀') ON CONFLICT DO NOTHING;

-- Atualizando a última mensagem da conversa
UPDATE tchat_conversa SET ultima_mensagem_id = 2 WHERE id = 1;

-- Redefinindo sequências (importante após inserts manuais com IDs)
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('tchat_conversa_id_seq', (SELECT MAX(id) FROM tchat_conversa));
SELECT setval('tchat_mensagem_id_seq', (SELECT MAX(id) FROM tchat_mensagem));
