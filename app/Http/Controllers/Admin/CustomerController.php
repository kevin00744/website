<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::with(['creator:id,name', 'store:id,name'])
            ->when($request->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")
            ))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters'   => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Customers/Edit', [
            'customer' => null,
            'stores'   => Store::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        Customer::create($data);

        return redirect()->route('admin.customers.index')->with('success', '顧客資料已建立。');
    }

    public function edit(Request $request, Customer $customer)
    {
        $me = $request->user();

        return Inertia::render('Admin/Customers/Edit', [
            'customer' => $customer,
            'stores'   => Store::orderBy('name')->get(['id', 'name']),
            'usages'   => $customer->usages()->with(['product:id,name', 'user:id,name', 'store:id,name'])->orderByDesc('created_at')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'can_record_usage' => $customer->store_id ? $me->canRecordUsage($customer->store_id) : $me->isAdmin(),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validateData($request));

        return redirect()->route('admin.customers.index')->with('success', '顧客資料已更新。');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return back()->with('success', '顧客資料已刪除。');
    }

    // 記錄這位顧客使用了哪個商品（會同步扣減該分店庫存）
    public function recordUsage(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'note'       => 'nullable|string',
        ]);

        $storeId = $customer->store_id;
        abort_unless($storeId, 422, '請先設定顧客所屬分店。');
        abort_unless($request->user()->canRecordUsage($storeId), 403);

        InventoryLog::apply([
            'store_id'        => $storeId,
            'product_id'      => $data['product_id'],
            'customer_id'     => $customer->id,
            'user_id'         => $request->user()->id,
            'type'            => InventoryLog::TYPE_USAGE,
            'quantity_change' => -abs($data['quantity']),
            'note'            => $data['note'] ?? null,
        ]);

        return back()->with('success', '使用紀錄已新增。');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|max:50',
            'line'     => 'nullable|string|max:255',
            'email'    => 'nullable|email|max:255',
            'address'  => 'nullable|string|max:255',
            'note'     => 'nullable|string',
            'store_id' => 'nullable|exists:stores,id',
        ]);
    }
}
