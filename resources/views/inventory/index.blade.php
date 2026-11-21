<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Inventario
            </h2>

            @can('manage-product')
                <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Nuevo producto
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-8">
            <x-flash-message />

            @error('product')
                <div role="alert" class="rounded-md bg-red-50 p-4 text-sm text-red-800">
                    {{ $message }}
                </div>
            @enderror

            {{-- Stock totals by category --}}
            <section aria-labelledby="totals-title" class="flex flex-col gap-3">
                <h3 id="totals-title" class="text-lg font-medium text-gray-900">Stock por categoría</h3>

                @if ($totals->isEmpty())
                    <p class="text-sm text-gray-500">Todavía no hay productos cargados.</p>
                @else
                    <dl class="grid grid-cols-2 gap-4 md:grid-cols-3">
                        @foreach ($totals as $total)
                            <div class="flex flex-col gap-1 bg-white p-4 shadow sm:rounded-lg">
                                <dt class="truncate text-sm text-gray-500">{{ $total->category }}</dt>
                                <dd class="text-2xl font-semibold text-gray-900">{{ number_format($total->units) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>

            {{-- Catalog filters --}}
            <form method="GET" action="{{ route('stock.index') }}" class="grid grid-cols-1 items-start gap-4 bg-white p-4 shadow sm:rounded-lg md:grid-cols-3">
                <div class="md:col-span-2">
                    <x-label for="q" value="Buscar por SKU o nombre" />
                    <x-input id="q" name="q" type="search" maxlength="80" value="{{ request('q') }}" class="mt-1 block w-full" />
                    <x-input-error for="q" class="mt-1" />
                </div>

                <div>
                    <x-label for="category" value="Categoría" />
                    <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todas</option>
                        @foreach ($totals as $total)
                            <option value="{{ $total->category }}" @selected(request('category') === $total->category)>{{ $total->category }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="category" class="mt-1" />
                </div>

                <div class="flex items-center gap-3 md:col-span-3">
                    <x-button>Filtrar</x-button>
                    <a href="{{ route('stock.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">Limpiar filtros</a>
                </div>
            </form>

            {{-- Catalog --}}
            <section aria-labelledby="catalog-title" class="overflow-hidden bg-white shadow sm:rounded-lg">
                <h3 id="catalog-title" class="p-4 text-lg font-medium text-gray-900">Catálogo</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">SKU</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Categoría</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium text-gray-500">Stock</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($products as $product)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-gray-900">{{ $product->sku }}</td>
                                    <td class="px-4 py-3 text-gray-900">{{ $product->name }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $product->category }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900" data-stock-id="{{ $product->id }}">{{ $product->stock }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-4">
                                            <a href="{{ route('movements.create', ['product_id' => $product->id, 'type' => 'in']) }}" class="text-green-700 hover:text-green-900">Entrada</a>
                                            <a href="{{ route('movements.create', ['product_id' => $product->id, 'type' => 'out']) }}" class="text-red-700 hover:text-red-900">Salida</a>
                                            <a href="{{ route('movements.index', ['product' => $product->id]) }}" class="text-gray-600 hover:text-gray-900">Movimientos</a>

                                            @can('manage-product')
                                                <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-800">Editar</a>

                                                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('¿Archivar este producto?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        @disabled($product->stock !== 0)
                                                        @if ($product->stock !== 0) title="Solo se puede archivar con stock cero" @endif
                                                        class="text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-40"
                                                    >
                                                        Archivar
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay productos que coincidan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4">
                    {{ $products->links() }}
                </div>
            </section>
        </div>
    </div>

    <script>
        const refreshStock = async () => {
            if (document.hidden) {
                return;
            }

            try {
                const response = await fetch(@js(route('stock.live')), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    return;
                }

                const rows = await response.json();

                rows.forEach(({ id, stock }) => {
                    document.querySelectorAll(`[data-stock-id="${id}"]`).forEach((cell) => {
                        cell.textContent = stock;
                    });
                });
            } catch (error) {
                // Network hiccup: keep the last known values and retry on the next tick.
            }
        };

        setInterval(refreshStock, 15000);
    </script>
</x-app-layout>