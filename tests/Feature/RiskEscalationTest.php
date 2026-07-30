<?php

namespace Tests\Feature;

use App\Models\Change;
use App\Models\DataInformation;
use App\Models\Risk;
use App\Models\SecurityProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers RiskEscalationResponseService — the auto-generated follow-ups
 * that fire when a Risk sits at Tinggi/Kritis (see Risk::booted()).
 */
class RiskEscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kritis_risk_automatically_creates_followups_in_other_modules(): void
    {
        $risk = Risk::create([
            'risk_code' => 'RSK-TEST-0001',
            'title' => 'Serangan DDoS Uji Coba',
            'category' => 'teknis',
            'likelihood' => 'kritis',
            'impact' => 'kritis',
            'risk_level' => 'kritis',
            'status' => 'open',
        ]);

        $this->assertTrue(
            SecurityProgram::where('dynamic_data->auto_generated_from_risk', $risk->risk_code)->exists()
        );
        $this->assertTrue(
            Change::where('dynamic_data->auto_generated_from_risk', $risk->risk_code)->exists()
        );
        $this->assertTrue(
            DataInformation::where('dynamic_data->auto_generated_from_risk', $risk->risk_code)->exists()
        );
    }

    public function test_followups_start_as_menunggu_verifikasi(): void
    {
        $risk = Risk::create([
            'risk_code' => 'RSK-TEST-0002',
            'title' => 'Kebocoran Data Uji Coba',
            'category' => 'teknis',
            'likelihood' => 'kritis',
            'impact' => 'tinggi',
            'risk_level' => 'kritis',
            'status' => 'open',
        ]);

        $program = SecurityProgram::where('dynamic_data->auto_generated_from_risk', $risk->risk_code)->first();

        $this->assertNotNull($program);
        $this->assertSame('menunggu_verifikasi', $program->verification_status);
    }

    public function test_rendah_risk_does_not_trigger_escalation(): void
    {
        $risk = Risk::create([
            'risk_code' => 'RSK-TEST-0003',
            'title' => 'Risiko Ringan Uji Coba',
            'category' => 'operasional',
            'likelihood' => 'rendah',
            'impact' => 'rendah',
            'risk_level' => 'rendah',
            'status' => 'open',
        ]);

        $this->assertFalse(
            SecurityProgram::where('dynamic_data->auto_generated_from_risk', $risk->risk_code)->exists()
        );
    }

    public function test_resaving_kritis_risk_does_not_duplicate_followups(): void
    {
        $risk = Risk::create([
            'risk_code' => 'RSK-TEST-0004',
            'title' => 'Risiko Kritis Berulang',
            'category' => 'teknis',
            'likelihood' => 'kritis',
            'impact' => 'kritis',
            'risk_level' => 'kritis',
            'status' => 'open',
        ]);

        // risk_level itself doesn't change here, so Risk::booted() shouldn't
        // refire the escalation service a second time.
        $risk->update(['status' => 'in_treatment']);

        $this->assertSame(
            1,
            SecurityProgram::where('dynamic_data->auto_generated_from_risk', $risk->risk_code)->count()
        );
    }
}
