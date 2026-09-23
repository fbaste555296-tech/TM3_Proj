<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model { protected $fillable=['name','phone','email','address','type']; public function jobs(){ return $this->hasMany(ServiceJob::class); } }
