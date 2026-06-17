<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'store_id', 'avatar', 'bio', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'created_by');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Role helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    public function canPublish(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    // ────────────────────────────────────────────
    // 帳號管理階層
    //   管理員 > 編輯 > 店長 > 店員
    //   - 管理員：可管理所有人
    //   - 編輯：可管理除管理員以外的所有人，可新增帳號、協助改密碼
    //   - 店長：只能管理「同一分店」的店員，可新增該分店的店員帳號
    //   - 店員：無法管理任何人
    // ────────────────────────────────────────────

    public const ROLES = ['admin', 'editor', 'manager', 'staff'];

    public const ROLE_LABELS = [
        'admin'   => '管理員',
        'editor'  => '編輯',
        'manager' => '店長',
        'staff'   => '店員',
    ];

    // 店長/店員必須隸屬於一個分店
    public const ROLES_REQUIRING_STORE = ['manager', 'staff'];

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    public function requiresStore(): bool
    {
        return in_array($this->role, self::ROLES_REQUIRING_STORE);
    }

    // 是否可以管理（檢視/編輯/改密碼）目標帳號；任何人都可以管理自己
    public function canManage(User $target): bool
    {
        if ($this->id === $target->id) {
            return true;
        }

        return match ($this->role) {
            'admin'   => true,
            'editor'  => $target->role !== 'admin',
            'manager' => $target->role === 'staff' && $target->store_id === $this->store_id,
            default   => false,
        };
    }

    // 是否可以刪除目標帳號（比一般管理更嚴格，僅管理員可刪除帳號）
    public function canDelete(User $target): bool
    {
        return $this->role === 'admin' && $this->id !== $target->id;
    }

    public function canCreateUsers(): bool
    {
        return in_array($this->role, ['admin', 'editor', 'manager']);
    }

    // 建立/變更帳號時，可指派的角色範圍
    public function assignableRoles(): array
    {
        return match ($this->role) {
            'admin'   => self::ROLES,
            'editor'  => ['editor', 'manager', 'staff'],
            'manager' => ['staff'],
            default   => [],
        };
    }

    // 在帳號列表中可以看到哪些人（不代表可以管理）
    public function visibleUsersQuery()
    {
        $query = self::query();

        return match ($this->role) {
            'admin', 'editor' => $query,
            'manager' => $query->where(fn ($q) => $q
                ->where(fn ($q2) => $q2->where('role', 'staff')->where('store_id', $this->store_id))
                ->orWhere('id', $this->id)),
            default   => $query->where('id', $this->id),
        };
    }

    // ────────────────────────────────────────────
    // 店面營運權限（分店／商品／庫存）
    //   編輯只負責網站介面內容與帳號開設，不參與實際店面營運，
    //   所以這裡跟一般「除管理員都能更動」的階層不同：
    //   - 管理員：所有分店皆可調整庫存、審核補貨請求、管理分店與商品目錄
    //   - 店長：只能調整/請求自己分店的庫存、記錄自己分店的顧客使用紀錄
    //   - 店員：只能查看自己分店的庫存、記錄自己分店的顧客使用紀錄
    //   - 編輯：完全不參與（canManageOperations() 為 false）
    // ────────────────────────────────────────────

    public function canManageOperations(): bool
    {
        return $this->role === 'admin';
    }

    public function canViewInventory(int $storeId): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return $this->store_id === $storeId;
    }

    public function canAdjustInventory(int $storeId): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return $this->role === 'manager' && $this->store_id === $storeId;
    }

    public function canRequestRestock(int $storeId): bool
    {
        return $this->role === 'manager' && $this->store_id === $storeId;
    }

    public function canReviewInventoryRequests(): bool
    {
        return $this->role === 'admin';
    }

    // 記錄顧客使用商品：管理員不限分店，店長/店員限自己分店
    public function canRecordUsage(int $storeId): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return in_array($this->role, ['manager', 'staff']) && $this->store_id === $storeId;
    }
}
