<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryLog extends Model
{
    protected $fillable = [
        'store_id', 'product_id', 'user_id', 'customer_id',
        'type', 'quantity_change', 'status', 'note',
        'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_REQUEST    = 'request';
    public const TYPE_USAGE      = 'usage';

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // 直接套用庫存異動（調整 / 使用），立即生效並寫入紀錄
    public static function apply(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $log = self::create([
                ...$attributes,
                'status' => self::STATUS_COMPLETED,
            ]);

            $inventory = Inventory::firstOrCreate(
                ['store_id' => $log->store_id, 'product_id' => $log->product_id],
                ['quantity' => 0]
            );
            $inventory->increment('quantity', $log->quantity_change);

            return $log;
        });
    }

    // 店長提出補貨請求，狀態為待審核，尚未變動庫存
    public static function request(array $attributes): self
    {
        return self::create([
            ...$attributes,
            'type'   => self::TYPE_REQUEST,
            'status' => self::STATUS_PENDING,
        ]);
    }

    public function approve(User $reviewer): void
    {
        DB::transaction(function () use ($reviewer) {
            $inventory = Inventory::firstOrCreate(
                ['store_id' => $this->store_id, 'product_id' => $this->product_id],
                ['quantity' => 0]
            );
            $inventory->increment('quantity', $this->quantity_change);

            $this->update([
                'status'      => self::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function reject(User $reviewer): void
    {
        $this->update([
            'status'      => self::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }
}
