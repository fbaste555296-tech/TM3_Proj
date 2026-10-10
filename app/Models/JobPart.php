<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JobPart extends Model { protected $fillable=['service_job_id','inventory_item_id','quantity','unit_price']; protected $casts=['unit_price'=>'decimal:2']; public function item(){return $this->belongsTo(InventoryItem::class,'inventory_item_id');} public function job(){return $this->belongsTo(ServiceJob::class,'service_job_id');} }
