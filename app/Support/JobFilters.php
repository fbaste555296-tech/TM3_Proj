<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobFilters
{
    public static function validate(Request $request, string $defaultRange = 'all'): array
    {
        $filters = $request->validate([
            'range' => ['nullable', Rule::in(['all', 'today', 'week', 'month', 'custom'])],
            'start_date' => 'required_if:range,custom|nullable|date_format:Y-m-d',
            'end_date' => 'required_if:range,custom|nullable|date_format:Y-m-d|after_or_equal:start_date',
            'technician_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:1000',
            'status' => ['nullable', Rule::in(['Pending', 'Dispatched', 'In Progress', 'Completed', 'Cancelled'])],
        ]);

        $range = $filters['range'] ?? $defaultRange;
        $start = match ($range) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'custom' => Carbon::parse($filters['start_date'])->startOfDay(),
            default => null,
        };
        $end = match ($range) {
            'today' => now()->endOfDay(),
            'week' => now()->endOfWeek(),
            'month' => now()->endOfMonth(),
            'custom' => Carbon::parse($filters['end_date'])->endOfDay(),
            default => null,
        };

        return [
            ...$filters,
            'range' => $range,
            'start' => $start,
            'end' => $end,
        ];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        if ($filters['start'] && $filters['end']) {
            $query->whereBetween('scheduled_at', [$filters['start'], $filters['end']]);
        }
        if (!empty($filters['technician_id'])) {
            $query->where('technician_id', $filters['technician_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['location'])) {
            $query->whereHas('customer', fn (Builder $customerQuery) => $customerQuery->where('address', $filters['location']));
        }

        return $query;
    }

    public static function isActive(array $filters): bool
    {
        return $filters['range'] !== 'all'
            || !empty($filters['technician_id'])
            || !empty($filters['location'])
            || !empty($filters['status']);
    }
}
