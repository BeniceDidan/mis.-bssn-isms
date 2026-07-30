<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Str;

/**
 * "Kode Personil" always ends up with SOME value once a record is saved —
 * left blank on the form, the system assigns a fresh random one, so the
 * field is never in an ambiguous "empty" state. It stays a plain editable
 * text input: two records only become linked (see HrRiskTable's
 * "Terverifikasi" tier) when a user deliberately overwrites one so it
 * matches the other's code — nothing is ever auto-matched by guessing.
 */
trait GeneratesPersonnelRef
{
    protected function ensurePersonnelRef(?string $value): string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : 'PSN-' . strtoupper(Str::random(6));
    }
}
