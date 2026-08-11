<?php

namespace App\Providers;

use App\Services\StorefrontViewData;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        View::composer('layouts.app', function ($view): void {
            $data = $view->getData();

            $missing = array_diff_key(
                app(StorefrontViewData::class)->layoutData(request()),
                $data
            );

            if ($missing !== []) {
                $view->with($missing);
            }
        });
    }
}
