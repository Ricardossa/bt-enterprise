-- ============================================================
-- BT QUEUE ENTERPRISE - DADOS INICIAIS (SEEDS V4.2)
-- ============================================================

-- 1. CONFIGURAÇÕES PADRÃO
INSERT OR IGNORE INTO configuracoes (chave, valor, tipo, descricao) VALUES
('master_url', 'http://api.brandaotech.com.br:8080/api/v1/sync.php', 'STRING', 'URL de sincronização com a Platform Master'),
('app_name', 'BT Queue Enterprise', 'STRING', 'Nome da aplicação local'),
('offline_limit_days', '7', 'INT', 'Dias permitidos de operação sem sincronização');

-- 2. SERVIÇO PADRÃO
INSERT OR IGNORE INTO servicos (codigo, nome, slug, prefixo, icone, cor, ordem) VALUES
('1', 'Atendimento Geral', 'atendimento-geral', 'A', '📋', '#1565C0', 1);

-- 3. GUICHÊ PADRÃO
INSERT OR IGNORE INTO guiches (codigo, nome, icone, cor) VALUES
('01', 'Mesa 01', '⚙️', '#1565C0');

-- 4. VÍNCULO INICIAL
INSERT OR IGNORE INTO guiche_servicos (guiche_id, servico_id) VALUES (1, 1);
