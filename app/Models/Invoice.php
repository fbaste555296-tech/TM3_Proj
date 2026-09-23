<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model { protected $fillable=['invoice_number','service_job_id','subtotal','tax','total','paid_amount','status']; protected $casts=['subtotal'=>'decimal:2','tax'=>'decimal:2','total'=>'decimal:2','paid_amount'=>'decimal:2']; public function job(){return $this->belongsTo(ServiceJob::class,'service_job_id');} public function payments(){return $this->hasMany(Payment::class);} }
