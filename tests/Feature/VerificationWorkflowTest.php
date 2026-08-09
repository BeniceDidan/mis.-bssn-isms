<?php

namespace Tests\Feature;

use App\Livewire\Risks\RiskFormModal;
use App\Models\Risk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Covers the User -> "menunggu_verifikasi" -> Admin ACC/Tolak workflow
 * (see RiskFormModal::save(), same pattern every module's FormModal uses).
 */
class VerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_submitted_risk_starts_menunggu_verifikasi(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        Livewire::test(RiskFormModal::class)
            ->set('title', 'Uji Coba Alur Verifikasi')
            ->set('category', 'operasional')
            ->set('likelihood', 'sedang')
            ->set('impact', 'sedang')
            ->call('save');

        $risk = Risk::where('title', 'Uji Coba Alur Verifikasi')->firstOrFail();

        $this->assertSame('menunggu_verifikasi', $risk->verification_status);
    }

    public function test_admin_submitted_risk_is_immediately_tervalidasi(): void
    {
        // admin_module must match the module being saved — admins are
        // scoped to exactly one module each (User::canAutoVerify()),
        // there's no blanket super-admin (see AdminModules).
        $admin = User::factory()->create(['role' => 'admin', 'admin_module' => 'risiko']);
        $this->actingAs($admin);

        Livewire::test(RiskFormModal::class)
            ->set('title', 'Uji Coba Admin Langsung Sah')
            ->set('category', 'operasional')
            ->set('likelihood', 'rendah')
            ->set('impact', 'rendah')
            ->call('save');

        $risk = Risk::where('title', 'Uji Coba Admin Langsung Sah')->firstOrFail();

        $this->assertSame('tervalidasi', $risk->verification_status);
    }

    public function test_personnel_ref_auto_generated_when_left_blank(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        Livewire::test(RiskFormModal::class)
            ->set('title', 'Uji Kode Personil Otomatis')
            ->set('category', 'teknis')
            ->set('likelihood', 'rendah')
            ->set('impact', 'rendah')
            ->set('personnel_ref', '')
            ->call('save');

        $risk = Risk::where('title', 'Uji Kode Personil Otomatis')->firstOrFail();

        $this->assertNotEmpty($risk->personnel_ref);
        $this->assertStringStartsWith('PSN-', $risk->personnel_ref);
    }

    public function test_a_non_authenticated_request_cannot_save(): void
    {
        Livewire::test(RiskFormModal::class)
            ->set('title', 'Tidak Boleh Tersimpan')
            ->set('category', 'operasional')
            ->set('likelihood', 'rendah')
            ->set('impact', 'rendah')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('risks', ['title' => 'Tidak Boleh Tersimpan']);
    }
}
