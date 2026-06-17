<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return Inertia::render('Admin/Stores/Index', [
            'stores' => Store::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return Inertia::render('Admin/Stores/Edit', ['store' => null]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        Store::create($this->validateData($request));

        return redirect()->route('admin.stores.index')->with('success', '分店已建立。');
    }

    public function edit(Request $request, Store $store)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return Inertia::render('Admin/Stores/Edit', ['store' => $store]);
    }

    public function update(Request $request, Store $store)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $store->update($this->validateData($request));

        return redirect()->route('admin.stores.index')->with('success', '分店已更新。');
    }

    public function destroy(Request $request, Store $store)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $store->delete();

        return back()->with('success', '分店已刪除。');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'      => 'required|string|max:255',
            'phone'     => 'nullable|string|max:50',
            'address'   => 'nullable|string|max:255',
            'note'      => 'nullable|string',
            'is_active' => 'boolean',
        ]);
    }
}
