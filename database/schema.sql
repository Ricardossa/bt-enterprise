-- ============================================================
-- BT QUEUE ENTERPRISE - SCHEMA OFICIAL V5.0 (NON-DESTRUCTIVE)
-- ============================================================

PRAGMA foreign_keys = ON;

-- 1. IDENTIDADE DA INSTALAÇÃO
CREATE TABLE IF NOT EXISTS system_info (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    installation_uuid TEXT NOT NULL UNIQUE,
    empresa_uuid TEXT,
    hostname TEXT,
    versao TEXT NOT NULL,
    build TEXT,
    schema_version INTEGER DEFAULT 2,
    ambiente TEXT DEFAULT 'PRODUCAO',
    php_version TEXT,
    sqlite_version TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_sync DATETIME,
    last_heartbeat DATETIME
);

-- 2. CLIENTES
CREATE TABLE IF NOT EXISTS clientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT NOT NULL UNIQUE,
    nome TEXT NOT NULL,
    documento TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. LICENÇAS
CREATE TABLE IF NOT EXISTS licencas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cliente_id INTEGER NOT NULL,
    chave TEXT NOT NULL UNIQUE,
    token TEXT,
    uuid TEXT,
    status TEXT NOT NULL DEFAULT 'ATIVA',
    validade DATETIME,
    ultima_validacao DATETIME,
    cache_assinatura TEXT,
    hardware_id TEXT,
    assinatura TEXT,
    offline_dias INTEGER DEFAULT 7,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(cliente_id) REFERENCES clientes(id)
);

-- 4. SERVIÇOS
CREATE TABLE IF NOT EXISTS servicos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo TEXT NOT NULL UNIQUE,
    nome TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    prefixo TEXT NOT NULL,
    icone TEXT DEFAULT '📋',
    cor TEXT DEFAULT '#1565C0',
    ordem INTEGER DEFAULT 0,
    tempo_medio INTEGER DEFAULT 10,
    ativo INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 5. GUICHÊS
CREATE TABLE IF NOT EXISTS guiches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo TEXT NOT NULL UNIQUE,
    nome TEXT NOT NULL,
    icone TEXT DEFAULT '⚙️',
    cor TEXT DEFAULT '#1565C0',
    ativo INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 6. VÍNCULO GUICHÊ-SERVIÇO
CREATE TABLE IF NOT EXISTS guiche_servicos (
    guiche_id INTEGER NOT NULL,
    servico_id INTEGER NOT NULL,
    PRIMARY KEY (guiche_id, servico_id),
    FOREIGN KEY(guiche_id) REFERENCES guiches(id) ON DELETE CASCADE,
    FOREIGN KEY(servico_id) REFERENCES servicos(id) ON DELETE CASCADE
);

-- 7. SENHAS
CREATE TABLE IF NOT EXISTS senhas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT NOT NULL UNIQUE,
    cliente_uuid TEXT NOT NULL,
    servico_id INTEGER,
    guiche_id INTEGER,
    device_id TEXT,
    codigo TEXT NOT NULL,
    numero INTEGER NOT NULL,
    prefixo TEXT NOT NULL,
    atendente TEXT,
    status TEXT NOT NULL DEFAULT 'AGUARDANDO',
    emitida_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    chamada_em DATETIME,
    finalizada_em DATETIME,
    sincronizado INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(servico_id) REFERENCES servicos(id),
    FOREIGN KEY(guiche_id) REFERENCES guiches(id)
);

-- 8. OPERADORES
CREATE TABLE IF NOT EXISTS operadores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    login TEXT NOT NULL UNIQUE,
    senha TEXT NOT NULL,
    nivel TEXT DEFAULT 'OPERADOR',
    guiche_id INTEGER,
    servico_id INTEGER,
    ativo INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(guiche_id) REFERENCES guiches(id),
    FOREIGN KEY(servico_id) REFERENCES servicos(id)
);

-- 9. CONFIGURAÇÕES
CREATE TABLE IF NOT EXISTS configuracoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chave TEXT NOT NULL UNIQUE,
    valor TEXT,
    tipo TEXT DEFAULT 'STRING',
    descricao TEXT,
    editavel INTEGER DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 10. SYNC QUEUE
CREATE TABLE IF NOT EXISTS sync_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    evento TEXT NOT NULL,
    entidade TEXT NOT NULL,
    referencia_id INTEGER,
    payload TEXT,
    prioridade INTEGER DEFAULT 0,
    sincronizado INTEGER DEFAULT 0,
    tentativas INTEGER DEFAULT 0,
    ultimo_erro TEXT,
    processado_em DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 11. ATIVIDADES
CREATE TABLE IF NOT EXISTS atividades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo TEXT DEFAULT 'INFO',
    nivel TEXT DEFAULT 'INFO',
    categoria TEXT DEFAULT 'SYSTEM',
    mensagem TEXT NOT NULL,
    usuario TEXT NULL,
    metadata TEXT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 12. PROMOÇÕES
CREATE TABLE IF NOT EXISTS promocoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT NOT NULL,
    descricao TEXT,
    preco TEXT,
    imagem TEXT,
    ordem INTEGER DEFAULT 0,
    ativo INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 13. CONTROLE DE MIGRAÇÕES
CREATE TABLE IF NOT EXISTS migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    arquivo TEXT UNIQUE,
    checksum TEXT,
    executado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ÍNDICES
CREATE INDEX IF NOT EXISTS idx_senhas_status ON senhas(status);
CREATE INDEX IF NOT EXISTS idx_senhas_uuid ON senhas(uuid);
CREATE INDEX IF NOT EXISTS idx_sync_status ON sync_queue(sincronizado);
CREATE INDEX IF NOT EXISTS idx_atividades_data ON atividades(data_criacao);
