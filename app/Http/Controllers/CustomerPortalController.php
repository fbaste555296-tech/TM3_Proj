<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ServiceJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'scheduled_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'time_slot' => ['required', Rule::in(array_keys(ServiceJob::TIME_SLOTS))],
            'address' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $data['scheduled_date'].' '.$data['time_slot']);
        if (! $scheduledAt->isFuture()) {
            throw ValidationException::withMessages([
                'time_slot' => 'Choose a future date and one of the available two-hour time slots.',
            ]);
        }

        $bookedSlots = ServiceJob::whereDate('scheduled_at', $data['scheduled_date'])
            ->whereIn('status', ServiceJob::ACTIVE_ASSIGNMENT_STATUSES)
            ->pluck('scheduled_at')
            ->map(fn ($dateTime) => Carbon::parse($dateTime)->format('H:i'))
            ->unique();

        if ($bookedSlots->count() >= count(ServiceJob::TIME_SLOTS)) {
            throw ValidationException::withMessages([
                'scheduled_date' => 'This day is fully booked. Please choose another available date.',
            ]);
        }

        if ($bookedSlots->contains($data['time_slot'])) {
            throw ValidationException::withMessages([
                'time_slot' => 'This time slot is already booked. Please choose another available time or date.',
            ]);
        }

        $customer = Customer::firstOrCreate(
            ['email' => $request->user()->email],
            ['name' => $request->user()->name, 'phone' => $request->user()->phone ?: 'Not provided', 'address' => $data['address'], 'type' => 'Residential']
        );
        $customer->update(['address' => $data['address']]);

        $job = ServiceJob::create([
            'job_number' => ServiceJob::nextJobNumber(),
            'customer_id' => $customer->id,
            'service_type' => $data['service_type'],
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => ServiceJob::SLOT_DURATION_MINUTES,
            'status' => 'Pending',
            'priority' => 'Normal',
            'notes' => $data['notes'] ?? null,
            'labor_cost' => 0,
        ]);

        return view('portal.confirmation', compact('job'));
    }
}
