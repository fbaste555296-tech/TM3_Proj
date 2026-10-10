<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\User;
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
            if (request()->routeIs('customers.*', 'inventory.*', 'invoices.*')) {
                return;
            }

            $view->with([
                'globalFilterTechnicians' => User::where('role', 'technician')->orderBy('name')->get(['id', 'name']),
                'globalFilterLocations' => Customer::whereNotNull('address')
                    ->where('address', '!=', '')
                    ->distinct()
                    ->orderBy('address')
                    ->pluck('address'),
                'globalFilterStatuses' => ['Pending', 'Dispatched', 'In Progress', 'Completed', 'Cancelled'],
                'globalFilterRange' => request()->query('range', request()->routeIs('reports.index') ? 'month' : 'all'),
            ]);
        });
    }
}
