<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NOTE: No "Manajemen Risiko" Excel file was found alongside the asset
 * inventory (per user confirmation). This schema is therefore NOT extracted
 * from a source spreadsheet — it follows the standard ISO 27005 / ISO 31000
 * risk register field set (threat, vulnerability, likelihood, impact,
 * treatment, ownership, review cadence) instead. Treat every column here as
 * a draft to be corrected against the real file once it's available.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risks', function (Blueprint $table) {
            $table->id();

            $table->string('risk_code', 20)->unique(); // RSK-2026-0001

            // Relational integrity (rule 7): a risk may originate from a
            // specific asset in the inventory.
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();

            $table->string('title');
            $table->string('category', 30)->index(); // App\Enums\RiskCategory
            $table->string('threat_source')->nullable();
            $table->text('vulnerability')->nullable();

            $table->string('likelihood', 20)->nullable(); // App\Enums\Level
            $table->string('impact', 20)->nullable(); // App\Enums\Level
            $table->string('risk_level', 20)->nullable()->index(); // App\Enums\Level, derived from likelihood x impact

            $table->string('risk_owner')->nullable();
            $table->string('treatment_strategy', 20)->nullable(); // App\Enums\TreatmentStrategy
            $table->string('status', 20)->default('open')->index(); // App\Enums\RiskStatus

            $table->date('identified_at')->nullable();
            $table->date('review_due_at')->nullable();

            // Anti-loss column for residual-risk detail, mitigation plans,
            // control references, cost estimates — anything not yet finalized.
            $table->jsonb('dynamic_data')->default('{}');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE INDEX risks_dynamic_data_gin_idx ON risks USING GIN (dynamic_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
