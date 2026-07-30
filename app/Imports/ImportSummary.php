<?php

namespace App\Imports;

class ImportSummary
{
    public int $created = 0;

    public int $updated = 0;

    /** @var array<int, array{sheet: string, row: int, message: string}> */
    public array $errors = [];

    public function incrementCreated(): void
    {
        $this->created++;
    }

    public function incrementUpdated(): void
    {
        $this->updated++;
    }

    public function addError(string $sheet, int $row, string $message): void
    {
        $this->errors[] = compact('sheet', 'row', 'message');
    }

    public function merge(self $other): void
    {
        $this->created += $other->created;
        $this->updated += $other->updated;
        $this->errors = array_merge($this->errors, $other->errors);
    }
}
