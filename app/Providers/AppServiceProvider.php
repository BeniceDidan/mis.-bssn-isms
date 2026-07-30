<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\Change;
use App\Models\DataInformation;
use App\Models\HrRisk;
use App\Models\KnowledgeActivity;
use App\Models\KnowledgeAsset;
use App\Models\KnowledgeExpert;
use App\Models\KnowledgeRisk;
use App\Models\Risk;
use App\Models\SecurityProgram;
use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Railway (and most PaaS hosts) terminate HTTPS at their edge proxy
        // and forward plain HTTP to the container — without this, Laravel
        // sees the request as http and generates http:// asset/redirect
        // URLs even though APP_URL is https, which browsers then block as
        // mixed content on the real https:// page. Harmless locally, where
        // APP_URL stays http://.
        if (Str::startsWith(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Keeps activity_logs.loggable_type readable ("asset", "risk")
        // instead of the full class name, and stays stable if models are
        // ever moved/renamed/namespaced differently.
        Relation::enforceMorphMap([
            'asset' => Asset::class,
            'risk' => Risk::class,
            'change' => Change::class,
            'data_information' => DataInformation::class,
            'hr_risk' => HrRisk::class,
            'knowledge_asset' => KnowledgeAsset::class,
            'service' => Service::class,
            'knowledge_expert' => KnowledgeExpert::class,
            'knowledge_activity' => KnowledgeActivity::class,
            'knowledge_risk' => KnowledgeRisk::class,
            'security_program' => SecurityProgram::class,
        ]);
    }
}
