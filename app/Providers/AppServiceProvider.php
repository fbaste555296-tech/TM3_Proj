<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view): void {
            if (request()->routeIs('customers.*', 'inventory.*', 'invoices.*')) {
                return;
            }

            $globalFilterLocations = request()->routeIs('jobs.*')
                ? []
                : Customer::whereNotNull('address')
                    ->where('address', '!=', '')
                    ->distinct()
                    ->orderBy('address')
                    ->pluck('address');

            $view->with([
                'globalFilterTechnicians' => User::where('role', 'technician')->orderBy('name')->get(['id', 'name']),
                'globalFilterLocations' => $globalFilterLocations,
                'globalFilterStatuses' => ['Pending', 'Dispatched', 'In Progress', 'Completed', 'Cancelled'],
                'globalFilterRange' => request()->query('range', request()->routeIs('reports.index') ? 'month' : 'all'),
            ]);
        });
    }
}
