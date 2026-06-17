<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::when($request->search, fn ($q) => $q
            ->where('name', 'like', "%{$request->search}%")
            ->orWhere('barcode', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters'  => $request->only('search'),
            'can_manage' => $request->user()->isAdmin(),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return Inertia::render('Admin/Products/Edit', ['product' => null]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        Product::create($this->validateData($request));

        return redirect()->route('admin.products.index')->with('success', '商品已建立。');
    }

    public function edit(Request $request, Product $product)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return Inertia::render('Admin/Products/Edit', ['product' => $product]);
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $product->update($this->validateData($request, $product));

        return redirect()->route('admin.products.index')->with('success', '商品已更新。');
    }

    public function destroy(Request $request, Product $product)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $product->delete();

        return back()->with('success', '商品已刪除。');
    }

    private function validateData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name'        => 'required|string|max:255',
            'barcode'     => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'description' => 'nullable|string',
            'price'       => 'nullable|numeric|min:0',
            'is_active'   => 'boolean',
        ]);
    }
}
