<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Many-to-many: a Layanan (service) can depend on several IT assets at once
 * ("Aset TIK Utama Terkait" in the source sheet holds a comma-separated
 * list), and one asset can back several services — unlike Risk/Change/
 * DataInformation/KnowledgeAsset, which each carry a single asset_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_assets');
    }
};
