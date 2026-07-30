<?php

namespace Tests\Unit;

use App\Livewire\Concerns\GeneratesPersonnelRef;
use PHPUnit\Framework\TestCase;

class GeneratesPersonnelRefTest extends TestCase
{
    private function subject(): object
    {
        return new class
        {
            use GeneratesPersonnelRef;

            public function ensure(?string $value): string
            {
                return $this->ensurePersonnelRef($value);
            }
        };
    }

    public function test_blank_value_generates_a_psn_code(): void
    {
        $code = $this->subject()->ensure(null);

        $this->assertStringStartsWith('PSN-', $code);
        $this->assertSame(10, strlen($code));
    }

    public function test_empty_string_also_generates_a_code(): void
    {
        $code = $this->subject()->ensure('   ');

        $this->assertStringStartsWith('PSN-', $code);
    }

    public function test_existing_value_is_preserved_and_trimmed(): void
    {
        $code = $this->subject()->ensure('  SPBE-SDM-001  ');

        $this->assertSame('SPBE-SDM-001', $code);
    }

    public function test_generated_codes_are_not_all_identical(): void
    {
        $subject = $this->subject();

        $this->assertNotSame($subject->ensure(null), $subject->ensure(null));
    }
}
