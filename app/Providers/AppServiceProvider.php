<?php

namespace App\Providers;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Observers\RbmCodificationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Observer global de codification + propagation d'avancement RBM
        Axe::observe(RbmCodificationObserver::class);
        Produit::observe(RbmCodificationObserver::class);
        SousProduit::observe(RbmCodificationObserver::class);
        Activite::observe(RbmCodificationObserver::class);
        Tache::observe(RbmCodificationObserver::class);
    }
}
