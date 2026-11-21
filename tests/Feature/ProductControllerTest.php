<?php

use App\Models\Product;

test('operators cannot manage products', function () {
    $this->actingAs(operator())->get(route('products.create'))->assertForbidden();
    $this->actingAs(operator())->post(route('products.store'), [])->assertForbidden();
});

test('admins can open the create and edit forms', function () {
    $product = Product::factory()->create(['name' => 'Destornillador']);

    $this->actingAs(admin())->get(route('products.create'))->assertOk()->assertSee('Nuevo producto');
    $this->actingAs(admin())->get(route('products.edit', $product))->assertOk()->assertSee('Destornillador');
});

test('admins can create a product', function () {
    $this->actingAs(admin())
        ->post(route('products.store'), [
            'name' => 'Llave inglesa',
            'sku' => 'LLV-0001',
            'category' => 'Herramientas',
            'description' => 'Ajustable',
        ])
        ->assertRedirect(route('stock.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('products', ['sku' => 'LLV-0001', 'stock' => 0]);
});

test('the sku must be unique when creating a product', function () {
    Product::factory()->create(['sku' => 'DUP-0001']);

    $this->actingAs(admin())
        ->post(route('products.store'), [
            'name' => 'Otro',
            'sku' => 'DUP-0001',
            'category' => 'Herramientas',
        ])
        ->assertSessionHasErrors('sku');
});

test('a product can be updated keeping its own sku', function () {
    $product = Product::factory()->create(['sku' => 'KEEP-001']);

    $this->actingAs(admin())
        ->put(route('products.update', $product), [
            'name' => 'Nombre nuevo',
            'sku' => 'KEEP-001',
            'category' => 'Herramientas',
        ])
        ->assertRedirect(route('stock.index'))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->name)->toBe('Nombre nuevo');
});

test('a product with stock cannot be archived', function () {
    $product = Product::factory()->create(['stock' => 3]);

    $this->actingAs(admin())
        ->delete(route('products.destroy', $product))
        ->assertSessionHasErrors('product');

    expect($product->fresh()->trashed())->toBeFalse();
});

test('a product with zero stock is archived and keeps its history', function () {
    $product = Product::factory()->create(['stock' => 0]);

    $this->actingAs(admin())
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('stock.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($product);
});
