<?php

use App\Models\Product;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('stock.index'))->assertRedirect(route('login'));
});

test('users without a stock role cannot see the inventory', function () {
    $user = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($user)->get(route('stock.index'))->assertForbidden();
});

test('operators can see the catalog and totals', function () {
    $product = Product::factory()->create(['name' => 'Taladro', 'category' => 'Herramientas', 'stock' => 7]);

    $this->actingAs(operator())
        ->get(route('stock.index'))
        ->assertOk()
        ->assertSee('Taladro')
        ->assertSee('Herramientas')
        ->assertSee(e(route('movements.create', ['product_id' => $product->id, 'type' => 'in'])), false)
        ->assertSee(route('movements.index', ['product' => $product->id]), false)
        ->assertDontSee('Nuevo producto');
});

test('admins see the product management actions', function () {
    Product::factory()->create();

    $this->actingAs(admin())
        ->get(route('stock.index'))
        ->assertOk()
        ->assertSee('Nuevo producto')
        ->assertSee('Archivar');
});

test('the catalog can be searched by sku or name', function () {
    Product::factory()->create(['sku' => 'ABC-1111', 'name' => 'Martillo']);
    Product::factory()->create(['sku' => 'XYZ-9999', 'name' => 'Sierra']);

    $this->actingAs(operator())
        ->get(route('stock.index', ['q' => 'ABC-1111']))
        ->assertOk()
        ->assertViewHas('products', fn ($products) => $products->count() === 1 && $products->first()->sku === 'ABC-1111');
});

test('the catalog can be filtered by category', function () {
    Product::factory()->create(['category' => 'Herramientas']);
    Product::factory()->create(['category' => 'Pinturas']);

    $this->actingAs(operator())
        ->get(route('stock.index', ['category' => 'Pinturas']))
        ->assertOk()
        ->assertViewHas('products', fn ($products) => $products->count() === 1 && $products->first()->category === 'Pinturas');
});

test('the catalog does not show the movement history', function () {
    $product = Product::factory()->create();
    $product->movements()->create([
        'user_id' => operator()->id,
        'type' => 'in',
        'quantity' => 7,
        'occurred_on' => now()->toDateString(),
        'supplier' => 'Ferretería Norte',
    ]);

    $this->actingAs(operator())
        ->get(route('stock.index'))
        ->assertOk()
        ->assertDontSee('Ferretería Norte');
});

test('the live endpoint returns the current stock of every product', function () {
    $product = Product::factory()->create(['stock' => 12]);

    $this->actingAs(operator())
        ->getJson(route('stock.live'))
        ->assertOk()
        ->assertJson([['id' => $product->id, 'stock' => 12]]);
});
