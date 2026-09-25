<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ServiceJob;
use Illuminate\Http\Request;

class CustomerPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $customer = Customer::where('email', $request->user()->email)->first();
        $appointments = $customer
            ? $customer->jobs()->latest('scheduled_at')->get()
            : collect();

        return view('portal.dashboard', compact('appointments'));
    }

    public function home()
    {
        if (auth()->check()) {
            $customer = Customer::where('email', auth()->user()->email)->first();
            $appointments = $customer
                ? $customer->jobs()->latest('scheduled_at')->get()
                : collect();

            return view('portal.customer-home', compact('appointments'));
        }

        return view('portal.home');
    }

    public function createAppointment(Request $request)
    {
        $customer = Customer::firstOrCreate(
            ['email' => $request->user()->email],
            ['name' => $request->user()->name, 'phone' => $request->user()->phone ?: 'Not provided', 'address' => 'To be confirmed', 'type' => 'Residential']
        );

        return view('portal.appointment', compact('customer'));
    }

    public function storeAppointment(Request $request)
    {
        $data = $request->validate([
            'service_type' => 'required|string|max:100',
            'scheduled_at' => 'required|date|after:now',
            'address' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $customer = Customer::firstOrCreate(
            ['email' => $request->user()->email],
            ['name' => $request->user()->name, 'phone' => $request->user()->phone ?: 'Not provided', 'address' => $data['address'], 'type' => 'Residential']
        );
        $customer->update(['address' => $data['address']]);

        $job = ServiceJob::create([
            'job_number' => 'TM3-'.now()->format('ymd').'-'.str_pad((string) (ServiceJob::count() + 1), 3, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'service_type' => $data['service_type'],
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => 60,
            'status' => 'Pending',
            'priority' => 'Normal',
            'notes' => $data['notes'],
            'labor_cost' => 0,
        ]);

        return view('portal.confirmation', compact('job'));
    }
}
