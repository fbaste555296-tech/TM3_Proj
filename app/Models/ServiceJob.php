<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceJob extends Model
{
    public const ACTIVE_ASSIGNMENT_STATUSES = ['Pending', 'Dispatched', 'In Progress'];

    public const SLOT_DURATION_MINUTES = 120;

    public const TIME_SLOTS = [
        '08:00' => '8:00 AM - 10:00 AM',
        '10:00' => '10:00 AM - 12:00 PM',
        '13:00' => '1:00 PM - 3:00 PM',
        '15:00' => '3:00 PM - 5:00 PM',
    ];

    protected $fillable = ['job_number', 'customer_id', 'technician_id', 'service_type', 'scheduled_at', 'duration_minutes', 'status', 'priority', 'notes', 'labor_cost'];

    protected $casts = ['scheduled_at' => 'datetime', 'labor_cost' => 'decimal:2'];

    public static function nextJobNumber(): string
    {
        $prefix = 'TM3-'.now()->format('ymd').'-';
        $highestSequence = static::where('job_number', 'like', $prefix.'%')
            ->pluck('job_number')
            ->map(fn (string $jobNumber) => (int) substr($jobNumber, strlen($prefix)))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($highestSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function parts()
    {
        return $this->hasMany(JobPart::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
