<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Kode Personil" — a user-assigned free-text identifier, editable on any
 * record in these 9 tables. Two records sharing the exact same value are
 * considered a confirmed link (see HrRiskTable::findCrossModuleLinks()),
 * on top of (not replacing) the existing name-word-matching guess. Unlike
 * a real foreign key, this needs no master "personnel" table and no
 * backfill of the ~15 existing free-text name columns — the user opts in
 * per record, at their own pace, only where they're sure it's the same
 * person/vendor. Nullable everywhere so nothing existing is affected until
 * someone fills it in.
 */
return new class extends Migration
{
    private array $tables = [
        'hr_risks',
        'assets',
        'risks',
        'changes',
        'data_informations',
        'services',
        'security_programs',
        'knowledge_activities',
        'knowledge_risks',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('personnel_ref', 50)->nullable()->index()->after('verification_status');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('personnel_ref');
            });
        }
    }
};
