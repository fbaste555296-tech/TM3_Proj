<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryItem extends Model { protected $fillable=['sku','name','category','stock','reorder_level','unit_price']; protected $casts=['unit_price'=>'decimal:2']; public function isLow(): bool { return $this->stock <= $this->reorder_level; } }
