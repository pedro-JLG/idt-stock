<?php

namespace App\Http\Controllers;

use App\Models\Movement;
use App\Models\Product;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MovementController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:in,out'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'product' => ['nullable', 'integer'],
        ]);

        $history = Movement::with('product');

        if ($type = $filters['type'] ?? null) {
            $history->where('type', $type);
        }

        if ($from = $filters['from'] ?? null) {
            $history->where('occurred_on', '>=', $from);
        }

        if ($to = $filters['to'] ?? null) {
            $history->where('occurred_on', '<=', $to);
        }

        $filteredProduct = null;

        if ($productId = $filters['product'] ?? null) {
            $history->where('product_id', $productId);
            $filteredProduct = Product::withTrashed()->find($productId);
        }

        return view('movements.index', [
            'movements' => $history->latest('occurred_on')->latest('id')->paginate(15)->withQueryString(),
            'filteredProduct' => $filteredProduct,
        ]);
    }

    public function create(Request $request)
    {
        $filters = $request->validate([
            'product_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'in:in,out'],
        ]);

        return view('movements.create', [
            'options' => Product::orderBy('name')->get(['id', 'sku', 'name']),
            'selectedProductId' => $filters['product_id'] ?? null,
            'selectedType' => $filters['type'] ?? 'in',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'supplier' => ['exclude_unless:type,in', 'required', 'string', 'max:120'],
            'reason' => ['exclude_unless:type,out', 'required', 'string', 'max:180'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            $quantity = (int) $data['quantity'];
            $next = $data['type'] === 'in' ? $product->stock + $quantity : $product->stock - $quantity;

            if ($next < 0 || $next > 99999) {
                throw ValidationException::withMessages([
                    'quantity' => $next < 0 ? 'Stock insuficiente' : 'Se superó el límite de stock',
                ]);
            }
            $product->stock = $next;
            $product->save();

            $product->movements()->create([
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'occurred_on' => $data['occurred_on'],
                'supplier' => $data['supplier'] ?? null,
                'reason' => $data['reason'] ?? null,
                'user_id' => $request->user()->id,
            ]);
        }, 3);

        return to_route('movements.index')->with('success', 'Movimiento registrado');
    }
}
