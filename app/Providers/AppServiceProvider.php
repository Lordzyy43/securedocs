<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Policies\AuditLogPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\DocumentSharePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(DocumentShare::class, DocumentSharePolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
