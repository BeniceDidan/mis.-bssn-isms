<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Generates human-readable display IDs (e.g. AST-2026-0001) as the primary
 * *display* identifier, per rule 6 — the auto-incrementing bigint `id` stays
 * the internal PK for fast joins, but is never surfaced in the UI or exports.
 *
 * Consuming models must implement getCodeColumn() and codePrefix().
 */
trait HasFormattedCode
{
    protected static function bootHasFormattedCode(): void
    {
        static::creating(function ($model) {
            $column = $model->getCodeColumn();

            if (empty($model->{$column})) {
                $model->{$column} = static::generateFormattedCode();
            }
        });
    }

    /**
     * Allocates the next sequence number for the current year under a row
     * lock, so concurrent creates (e.g. a bulk Excel import) can't collide
     * on the same code.
     */
    public static function generateFormattedCode(): string
    {
        $prefix = static::codePrefix();
        $year = now()->year;
        $column = (new static())->getCodeColumn();

        return DB::transaction(function () use ($prefix, $year, $column) {
            $last = static::withTrashed()
                ->where($column, 'like', "{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            $nextSequence = $last ? ((int) substr($last, -4)) + 1 : 1;

            return sprintf('%s-%d-%04d', $prefix, $year, $nextSequence);
        });
    }
}
