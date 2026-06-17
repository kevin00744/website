<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $stores = Store::orderBy('name')
            ->get()
            ->filter(fn (Store $s) => $me->canViewInventory($s->id))
            ->values();

        abort_if($stores->isEmpty(), 403);

        $store = $stores->firstWhere('id', (int) $request->input('store_id')) ?? $stores->first();
        abort_unless($me->canViewInventory($store->id), 403);

        $levels = Inventory::where('store_id', $store->id)->pluck('quantity', 'product_id');

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'barcode'  => $p->barcode,
                'quantity' => $levels->get($p->id, 0),
            ]);

        $pendingRequests = InventoryLog::with(['product:id,name', 'user:id,name'])
            ->where('store_id', $store->id)
            ->where('type', InventoryLog::TYPE_REQUEST)
            ->where('status', InventoryLog::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Inventory/Index', [
            'stores'           => $stores->map(fn (Store $s) => ['id' => $s->id, 'name' => $s->name]),
            'selected_store'   => ['id' => $store->id, 'name' => $store->name],
            'products'         => $products,
            'pending_requests' => $pendingRequests,
            'can_adjust'       => $me->canAdjustInventory($store->id),
            'can_request'      => $me->canRequestRestock($store->id),
            'can_review'       => $me->canReviewInventoryRequests(),
        ]);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'store_id'        => 'required|exists:stores,id',
            'product_id'      => 'required|exists:products,id',
            'quantity_change' => 'required|integer|not_in:0',
            'note'            => 'nullable|string',
        ]);

        abort_unless($request->user()->canAdjustInventory((int) $data['store_id']), 403);

        InventoryLog::apply([
            ...$data,
            'user_id' => $request->user()->id,
            'type'    => InventoryLog::TYPE_ADJUSTMENT,
        ]);

        return back()->with('success', '庫存已更新。');
    }

    public function requestRestock(Request $request)
    {
        $data = $request->validate([
            'store_id'        => 'required|exists:stores,id',
            'product_id'      => 'required|exists:products,id',
            'quantity_change' => 'required|integer|min:1',
            'note'            => 'nullable|string',
        ]);

        abort_unless($request->user()->canRequestRestock((int) $data['store_id']), 403);

        InventoryLog::request([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', '補貨請求已送出，待審核。');
    }

    public function approve(Request $request, InventoryLog $log)
    {
        abort_unless($request->user()->canReviewInventoryRequests(), 403);
        abort_unless($log->status === InventoryLog::STATUS_PENDING, 404);

        $log->approve($request->user());

        return back()->with('success', '補貨請求已核准。');
    }

    public function reject(Request $request, InventoryLog $log)
    {
        abort_unless($request->user()->canReviewInventoryRequests(), 403);
        abort_unless($log->status === InventoryLog::STATUS_PENDING, 404);

        $log->reject($request->user());

        return back()->with('success', '補貨請求已駁回。');
    }

    public function logs(Request $request)
    {
        $me = $request->user();
        abort_unless(in_array($me->role, ['admin', 'manager']), 403);

        $stores = Store::orderBy('name')->get()->filter(fn (Store $s) => $me->canViewInventory($s->id))->values();

        $logs = InventoryLog::with(['store:id,name', 'product:id,name', 'user:id,name', 'customer:id,name', 'reviewer:id,name'])
            ->whereIn('store_id', $stores->pluck('id'))
            ->when($request->store_id, fn ($q) => $q->where('store_id', $request->store_id))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Inventory/Logs', [
            'logs'   => $logs,
            'stores' => $stores->map(fn (Store $s) => ['id' => $s->id, 'name' => $s->name]),
            'filters' => $request->only('store_id'),
        ]);
    }
}
