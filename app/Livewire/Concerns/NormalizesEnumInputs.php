<?php

namespace App\Livewire\Concerns;

/**
 * A blank optional <select> bound to a nullable PHP-enum-cast column
 * submits as an empty string, not null — but Eloquent's enum cast throws
 * ValueError trying to back an enum with "" (no enum case has that value).
 * `validate()` alone doesn't catch this: 'nullable' accepts "" as valid,
 * it just doesn't require a value. Call this on the validated payload
 * before create()/update() for every nullable enum-backed key.
 */
trait NormalizesEnumInputs
{
    protected function nullifyBlankEnums(array $payload, array $keys): array
    {
        foreach ($keys as $key) {
            if (($payload[$key] ?? null) === '') {
                $payload[$key] = null;
            }
        }

        return $payload;
    }
}
