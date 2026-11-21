<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);

        $catalog = Product::query();

        if ($search = $filters['q'] ?? null) {
            $catalog->where(function ($query) use ($search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($category = $filters['category'] ?? null) {
            $catalog->where('category', $category);
        }

        return view('inventory.index', [
            'products' => $catalog->orderBy('name')->paginate(12, ['*'], 'page')->withQueryString(),
            'totals' => Product::query()->selectRaw('category, SUM(stock) as units')
                ->groupBy('category')->orderBy('category')->get(),
        ]);

    }

    public function live()
    {
        return response()->json(Product::orderBy('id')->get(['id', 'stock']))
            ->header('Cache-Control', 'private, no-store');
    }
}
