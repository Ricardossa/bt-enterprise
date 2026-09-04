<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Throwable;

class ServicoService
{
    public function listar(): array
    {
        $rows = Database::fetchAll(
            "SELECT id, codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, preco,
                    promo_ativa, promo_desconto, promo_dias, created_at, updated_at
             FROM servicos
             WHERE ativo = 1
             ORDER BY ordem ASC, nome ASC"
        );

        foreach ($rows as &$r) {
            $calc = self::getPrecoVigente((int)$r['id']);
            $r['current_price'] = $calc['preco'];
            $r['is_promo_today'] = $calc['is_promo'];
            $r['preco_original'] = $calc['original'];
        }
        return $rows;
    }

    public function buscar(int $id): ?array
    {
        $r = Database::fetch(
            "SELECT id, codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, preco,
                    promo_ativa, promo_desconto, promo_dias, ativo, created_at, updated_at
             FROM servicos
             WHERE id = ?",
            [$id]
        );

        if ($r) {
            $calc = self::getPrecoVigente((int)$r['id']);
            $r['current_price'] = $calc['preco'];
            $r['is_promo_today'] = $calc['is_promo'];
            $r['preco_original'] = $calc['original'];
        }
        return $r;
    }

    public function adicionar(
        string $codigo,
        string $nome,
        string $slug,
        string $prefixo,
        string $icone,
        string $cor,
        int $ordem,
        int $tempo_medio,
        float $preco = 0,
        int $promo_ativa = 0,
        float $promo_desconto = 20.00,
        string $promo_dias = '[1,2,3]'
    ): array {
        try {
            $existeCodigo = Database::fetch("SELECT id FROM servicos WHERE codigo = ?", [$codigo]);
            if ($existeCodigo) {
                return ['success' => false, 'message' => 'DUPLICATE_CODE'];
            }

            $existeSlug = Database::fetch("SELECT id FROM servicos WHERE slug = ?", [$slug]);
            if ($existeSlug) {
                return ['success' => false, 'message' => 'DUPLICATE_SLUG'];
            }

            Database::execute(
                "INSERT INTO servicos (codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, preco, promo_ativa, promo_desconto, promo_dias)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$codigo, $nome, $slug, $prefixo, $icone, $cor, $ordem, $tempo_medio, $preco, $promo_ativa, $promo_desconto, $promo_dias]
            );

            return ['success' => true];
// ...
    public function editar(
        int $id,
        string $codigo,
        string $nome,
        string $slug,
        string $prefixo,
        string $icone,
        string $cor,
        int $ordem,
        int $tempo_medio,
        float $preco = 0,
        int $promo_ativa = 0,
        float $promo_desconto = 20.00,
        string $promo_dias = '[1,2,3]'
    ): array {
        try {
            $existeCodigo = Database::fetch("SELECT id FROM servicos WHERE codigo = ? AND id != ?", [$codigo, $id]);
            if ($existeCodigo) {
                return ['success' => false, 'message' => 'DUPLICATE_CODE'];
            }

            $existeSlug = Database::fetch("SELECT id FROM servicos WHERE slug = ? AND id != ?", [$slug, $id]);
            if ($existeSlug) {
                return ['success' => false, 'message' => 'DUPLICATE_SLUG'];
            }

            Database::execute(
                "UPDATE servicos
                 SET codigo = ?, nome = ?, slug = ?, prefixo = ?, icone = ?, cor = ?, ordem = ?, tempo_medio = ?, preco = ?,
                     promo_ativa = ?, promo_desconto = ?, promo_dias = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?",
                [$codigo, $nome, $slug, $prefixo, $icone, $cor, $ordem, $tempo_medio, $preco, $promo_ativa, $promo_desconto, $promo_dias, $id]
            );

            return ['success' => true];
// ...
    public function excluir(int $id): array
    {
        try {
            Database::execute(
                "UPDATE servicos 
                 SET ativo = 0, updated_at = CURRENT_TIMESTAMP 
                 WHERE id = ?", 
                [$id]
            );
            return ['success' => true];
        } catch (Throwable $e) {
            Logger::error("Erro ao excluir serviço (ID $id): " . $e->getMessage());
            return ['success' => false, 'message' => 'INTERNAL_ERROR'];
        }
    }

    /**
     * [v7.8.9] Calcula o preço real do serviço para uma data específica
     */
    public static function getPrecoVigente(int $id, ?string $dataAlvo = null): array
    {
        $s = Database::fetch("SELECT preco, promo_ativa, promo_desconto, promo_dias FROM servicos WHERE id = ?", [$id]);
        if (!$s) return ['preco' => 0, 'is_promo' => false, 'original' => 0];

        $precoOriginal = (float)($s['preco'] ?? 0);
        $precoFinal = $precoOriginal;
        $isPromo = false;

        if (($s['promo_ativa'] ?? 0) == 1) {
            $diaSemana = (int)date('w', $dataAlvo ? strtotime($dataAlvo) : time());
            $diasPromo = json_decode($s['promo_dias'] ?? '[]', true);

            if (in_array($diaSemana, $diasPromo)) {
                $desconto = (float)($s['promo_desconto'] ?? 20.00);
                $precoFinal = $precoOriginal * (1 - ($desconto / 100));
                $isPromo = true;
            }
        }

        return [
            'preco' => (float)$precoFinal,
            'is_promo' => $isPromo,
            'original' => $precoOriginal
        ];
    }
}
