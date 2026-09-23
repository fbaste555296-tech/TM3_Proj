<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ServiceJob extends Model { protected $fillable=['job_number','customer_id','technician_id','service_type','scheduled_at','duration_minutes','status','priority','notes','labor_cost']; protected $casts=['scheduled_at'=>'datetime','labor_cost'=>'decimal:2']; public function customer(){return $this->belongsTo(Customer::class);} public function technician(){return $this->belongsTo(User::class,'technician_id');} public function parts(){return $this->hasMany(JobPart::class);} public function invoice(){return $this->hasOne(Invoice::class);} }
