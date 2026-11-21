<?php

use App\Models\Movement;
use App\Models\Product;
use App\Models\User;

/**
 * @return array<string, mixed>
 */
function movementPayload(Product $product, array $overrides = []): array
{
    return array_merge([
        'product_id' => $product->id,
        'type' => 'in',
        'quantity' => 5,
        'occurred_on' => now()->toDateString(),
        'supplier' => 'Proveedor SA',
    ], $overrides);
}

test('guests are redirected from the movements pages', function () {
    $this->get(route('movements.index'))->assertRedirect(route('login'));
    $this->get(route('movements.create'))->assertRedirect(route('login'));
});

test('users without a stock role cannot see the movements pages', function () {
    $user = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($user)->get(route('movements.index'))->assertForbidden();
    $this->actingAs($user)->get(route('movements.create'))->assertForbidden();
});

test('operators can see the movement history', function () {
    $product = Product::factory()->create(['sku' => 'HIS-0001', 'name' => 'Taladro']);
    $product->movements()->create([
        'user_id' => operator()->id,
        'type' => 'in',
        'quantity' => 7,
        'occurred_on' => now()->toDateString(),
        'supplier' => 'Ferretería Norte',
    ]);

    $this->actingAs(operator())
        ->get(route('movements.index'))
        ->assertOk()
        ->assertSee('Ferretería Norte')
        ->assertSee('Taladro')
        ->assertSee(route('stock.index', ['q' => 'HIS-0001']), false)
        ->assertSee(route('movements.create'), false);
});

test('the history keeps archived products with their product eager loaded', function () {
    $product = Product::factory()->create(['stock' => 0]);

    foreach (range(1, 3) as $quantity) {
        $product->movements()->create([
            'user_id' => operator()->id,
            'type' => 'in',
            'quantity' => $quantity,
            'occurred_on' => now()->toDateString(),
            'supplier' => 'Proveedor SA',
        ]);
    }

    $product->delete();

    $this->actingAs(operator())
        ->get(route('movements.index'))
        ->assertOk()
        ->assertSee('(archivado)')
        ->assertViewHas('movements', fn ($movements) => $movements->count() === 3
            && $movements->every(fn ($movement) => $movement->relationLoaded('product')));
});

test('the history can be filtered by type and date range', function () {
    $product = Product::factory()->create();
    $user = operator();

    foreach ([['in', '2026-01-10'], ['out', '2026-01-20'], ['out', '2026-03-01']] as [$type, $date]) {
        $product->movements()->create([
            'user_id' => $user->id,
            'type' => $type,
            'quantity' => 1,
            'occurred_on' => $date,
            'supplier' => $type === 'in' ? 'Proveedor SA' : null,
            'reason' => $type === 'out' ? 'Venta' : null,
        ]);
    }

    $this->actingAs($user)
        ->get(route('movements.index', ['type' => 'out', 'from' => '2026-01-01', 'to' => '2026-02-01']))
        ->assertOk()
        ->assertViewHas('movements', fn ($movements) => $movements->count() === 1
            && $movements->first()->occurred_on->toDateString() === '2026-01-20');
});

test('the history can be filtered by product', function () {
    $user = operator();
    $first = Product::factory()->create(['name' => 'Martillo']);
    $second = Product::factory()->create(['name' => 'Sierra']);

    foreach ([$first, $second] as $product) {
        $product->movements()->create([
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 2,
            'occurred_on' => now()->toDateString(),
            'supplier' => 'Proveedor SA',
        ]);
    }

    $this->actingAs($user)
        ->get(route('movements.index', ['product' => $first->id]))
        ->assertOk()
        ->assertSee('Quitar filtro de producto')
        ->assertViewHas('movements', fn ($movements) => $movements->count() === 1
            && $movements->first()->product_id === $first->id)
        ->assertViewHas('filteredProduct', fn ($product) => $product->is($first));
});

test('invalid history filters are rejected', function () {
    $this->actingAs(operator())
        ->get(route('movements.index', ['type' => 'sideways']))
        ->assertSessionHasErrors('type');
});

test('the movement form preselects the product and type from the query string', function () {
    $product = Product::factory()->create(['sku' => 'PRE-0001']);

    $this->actingAs(operator())
        ->get(route('movements.create', ['product_id' => $product->id, 'type' => 'out']))
        ->assertOk()
        ->assertSee('value="'.$product->id.'" selected', false)
        ->assertSee("type: 'out'", false)
        ->assertSee('Cancelar');
});

test('the movement form defaults to an entry without a preselected product', function () {
    Product::factory()->create();

    $this->actingAs(operator())
        ->get(route('movements.create'))
        ->assertOk()
        ->assertSee("type: 'in'", false);
});

test('an entry movement increases the stock and records the user', function () {
    $user = operator();
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs($user)
        ->post(route('movements.store'), movementPayload($product, ['quantity' => 5]))
        ->assertRedirect(route('movements.index'))
        ->assertSessionHas('success');

    expect($product->fresh()->stock)->toBe(15);

    $this->assertDatabaseHas('movements', [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'type' => 'in',
        'quantity' => 5,
        'supplier' => 'Proveedor SA',
        'reason' => null,
    ]);
});

test('an exit movement decreases the stock', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(operator())
        ->post(route('movements.store'), movementPayload($product, [
            'type' => 'out',
            'quantity' => 4,
            'supplier' => null,
            'reason' => 'Venta mostrador',
        ]))
        ->assertRedirect(route('movements.index'))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(6);

    $this->assertDatabaseHas('movements', [
        'product_id' => $product->id,
        'type' => 'out',
        'reason' => 'Venta mostrador',
        'supplier' => null,
    ]);
});

test('an exit larger than the available stock is rejected', function () {
    $product = Product::factory()->create(['stock' => 2]);

    $this->actingAs(operator())
        ->post(route('movements.store'), movementPayload($product, [
            'type' => 'out',
            'quantity' => 3,
            'supplier' => null,
            'reason' => 'Venta mostrador',
        ]))
        ->assertSessionHasErrors('quantity');

    expect($product->fresh()->stock)->toBe(2);
    expect(Movement::count())->toBe(0);
});

test('an entry requires a supplier and an exit requires a reason', function () {
    $product = Product::factory()->create(['stock' => 5]);

    $this->actingAs(operator())
        ->post(route('movements.store'), movementPayload($product, ['supplier' => '']))
        ->assertSessionHasErrors('supplier');

    $this->actingAs(operator())
        ->post(route('movements.store'), movementPayload($product, ['type' => 'out', 'supplier' => null]))
        ->assertSessionHasErrors('reason');
});

it('conserva el saldo y el historial si una salida excede el stock', function () {
    $operador = User::factory()->create(['role' => 'operator']);
    $product = Product::query()->create([
        'name' => 'Cable USB',
        'category' => 'Insumos',
        'sku' => 'DEMO-48',
    ]);

    $base = [
        'product_id' => $product->id,
        'occurred_on' => today()->toDateString(),
    ];

    $this->actingAs($operador)->post(route('movements.store'), $base + [
        'type' => 'in',
        'quantity' => 10,
        'supplier' => 'Proveedor demo',
    ])->assertRedirectToRoute('movements.index')->assertSessionHasNoErrors();

    $this->post(route('movements.store'), $base + [
        'type' => 'out',
        'quantity' => 4,
        'reason' => 'Pedido',
    ])->assertRedirectToRoute('movements.index')->assertSessionHasNoErrors();

    $this->from(route('stock.index'))->post(route('movements.store'), $base + [
        'type' => 'out',
        'quantity' => 7,
        'reason' => 'Pedido',
    ])->assertRedirectToRoute('stock.index')->assertSessionHasErrors('quantity');

    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 6]);
    $this->assertDatabaseCount('movements', 2);
});
