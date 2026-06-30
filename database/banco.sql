CREATE DATABASE IF NOT EXISTS calendarioia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE calendarioia;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    telefone VARCHAR(20) NULL,
    plano ENUM('trial', 'ativo', 'cancelado') NOT NULL DEFAULT 'trial',
    plano_expira_em DATETIME NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    token_acesso_google TEXT NULL,
    token_refresh_google TEXT NULL,
    token_google_expira_em DATETIME NULL,
    fuso_horario VARCHAR(50) NOT NULL DEFAULT 'America/Mexico_City',
    modo_silencio TINYINT(1) NOT NULL DEFAULT 0,
    antecedencia_lembrete_min INT NOT NULL DEFAULT 30,
    token_recuperacao VARCHAR(255) NULL,
    token_recuperacao_expira DATETIME NULL,
    stripe_customer_id VARCHAR(255) NULL,
    stripe_subscription_id VARCHAR(255) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deletado TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NULL,
    id_google_event VARCHAR(255) NULL,
    lembrete TINYINT(1) NOT NULL DEFAULT 1,
    lembrete_enviado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE IF NOT EXISTS logs_mensagens (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    direcao ENUM('entrada', 'saida') NOT NULL,
    conteudo TEXT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE IF NOT EXISTS sessoes_conversa (
    id_sessao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    contexto TEXT NULL,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE IF NOT EXISTS planos (
    id_plano INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    descricao VARCHAR(255) NULL,
    ciclo ENUM('mensal', 'trimestral', 'anual') NOT NULL DEFAULT 'mensal',
    preco DECIMAL(10,2) NOT NULL DEFAULT 0,
    dias_teste INT NOT NULL DEFAULT 0,
    etiqueta_texto VARCHAR(100) NULL,
    etiqueta_cor VARCHAR(20) NOT NULL DEFAULT 'amarelo',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS configuracoes (
    chave VARCHAR(100) PRIMARY KEY,
    valor VARCHAR(255) NOT NULL,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS administradores (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) NOT NULL UNIQUE,
    emoji VARCHAR(10) NOT NULL,
    cor VARCHAR(20) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO categorias (chave, emoji, cor, ordem) VALUES
    ('alimentacao', '🍔', '#F59E0B', 1),
    ('transporte', '🚗', '#3B82F6', 2),
    ('entretenimento', '🎬', '#8B5CF6', 3),
    ('servicos', '⚡', '#EF4444', 4),
    ('saude', '💊', '#10B981', 5),
    ('educacao', '📚', '#6366F1', 6),
    ('moradia', '🏠', '#F97316', 7),
    ('compras', '🛍️', '#EC4899', 8),
    ('salario', '💼', '#0EA5E9', 9),
    ('entrada', '💰', '#22C55E', 10),
    ('outros', '📦', '#94A3B8', 11);

CREATE TABLE IF NOT EXISTS transacoes (
    id_transacao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    categoria VARCHAR(50) NOT NULL DEFAULT 'outros',
    data_transacao DATE NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE INDEX idx_transacoes_usuario_data ON transacoes (id_usuario, data_transacao);
CREATE INDEX idx_transacoes_usuario_tipo ON transacoes (id_usuario, tipo, data_transacao);

-- Admin padrão (email: admin@gmail.com / senha: Admin@123)
INSERT IGNORE INTO administradores (nome, email, senha_hash) VALUES (
    'Caio Gomes',
    'admin@gmail.com',
    '$2y$10$W0aZgyoIytf3P2xkN/bSLOpZwkMbzuTWtz1WSxmyYBPboLFbIVVCi'
);
