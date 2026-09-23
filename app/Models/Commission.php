<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Commission extends Model { protected $fillable=['service_job_id','technician_id','rate','amount','status']; protected $casts=['rate'=>'decimal:2','amount'=>'decimal:2']; public function technician(){return $this->belongsTo(User::class,'technician_id');} public function job(){return $this->belongsTo(ServiceJob::class,'service_job_id');} }
