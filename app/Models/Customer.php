<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'line', 'email', 'address', 'note', 'created_by', 'store_id',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function usages()
    {
        return $this->hasMany(InventoryLog::class, 'customer_id')->where('type', InventoryLog::TYPE_USAGE);
    }
}
