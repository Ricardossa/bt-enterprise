-- MIGRATION: BT SCHEDULER v1.1 - JANELAS DE LIBERAÇÃO
-- Permite configurar quando a agenda de um dia específico fica visível para o público.

ALTER TABLE agenda_regras ADD COLUMN liberacao_dia_semana INTEGER NULL; -- Dia que abre (0-6)
ALTER TABLE agenda_regras ADD COLUMN liberacao_hora_inicio TEXT DEFAULT '00:00';
ALTER TABLE agenda_regras ADD COLUMN liberacao_hora_fim TEXT DEFAULT '23:59';
