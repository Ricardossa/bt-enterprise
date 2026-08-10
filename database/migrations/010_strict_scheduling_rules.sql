-- MIGRATION: BT SCHEDULER v1.2 - REGRAS DE COMPLIANCE
-- Implementação de travas por fornecedor, limites mensais e suspensões.

ALTER TABLE senhas ADD COLUMN whatsapp TEXT;
ALTER TABLE senhas ADD COLUMN cancel_token TEXT;

CREATE TABLE IF NOT EXISTS agenda_suspensoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identificador TEXT NOT NULL UNIQUE, -- Nome ou WhatsApp
    motivo TEXT,
    data_fim DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
