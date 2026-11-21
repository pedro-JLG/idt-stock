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
            @if (session('success'))
                <div role="status" class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

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
                    <dl class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        @foreach ($totals as $total)
                            <div class="flex flex-col gap-1 bg-white p-4 shadow sm:rounded-lg">
                                <dt class="truncate text-sm text-gray-500">{{ $total->category }}</dt>
                                <dd class="text-2xl font-semibold text-gray-900">{{ number_format($total->units) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>

            {{-- Filters --}}
            <form method="GET" action="{{ route('stock.index') }}" class="grid grid-cols-1 items-start gap-4 bg-white p-4 shadow sm:rounded-lg md:grid-cols-6">
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

                <div>
                    <x-label for="type" value="Tipo de movimiento" />
                    <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        <option value="in" @selected(request('type') === 'in')>Entradas</option>
                        <option value="out" @selected(request('type') === 'out')>Salidas</option>
                    </select>
                    <x-input-error for="type" class="mt-1" />
                </div>

                <div>
                    <x-label for="from" value="Desde" />
                    <x-input id="from" name="from" type="date" value="{{ request('from') }}" class="mt-1 block w-full" />
                    <x-input-error for="from" class="mt-1" />
                </div>

                <div>
                    <x-label for="to" value="Hasta" />
                    <x-input id="to" name="to" type="date" value="{{ request('to') }}" class="mt-1 block w-full" />
                    <x-input-error for="to" class="mt-1" />
                </div>

                <div class="flex items-center gap-3 md:col-span-6">
                    <x-button>Filtrar</x-button>
                    <a href="{{ route('stock.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">Limpiar filtros</a>
                </div>
            </form>

            {{-- New movement --}}
            <section aria-labelledby="movement-title" class="bg-white p-6 shadow sm:rounded-lg">
                <h3 id="movement-title" class="text-lg font-medium text-gray-900">Registrar movimiento</h3>

                <form
                    method="POST"
                    action="{{ route('movements.store') }}"
                    x-data="{ type: @js(old('type', 'in')) }"
                    class="mt-4 grid grid-cols-1 items-start gap-4 md:grid-cols-6"
                >
                    @csrf

                    <div class="md:col-span-2">
                        <x-label for="product_id" value="Producto" />
                        <select id="product_id" name="product_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccioná un producto</option>
                            @foreach ($options as $option)
                                <option value="{{ $option->id }}" @selected((string) old('product_id') === (string) $option->id)>
                                    {{ $option->sku }} · {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error for="product_id" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="movement_type" value="Tipo" />
                        <select id="movement_type" name="type" x-model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="in">Entrada</option>
                            <option value="out">Salida</option>
                        </select>
                        <x-input-error for="type" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="quantity" value="Cantidad" />
                        <x-input id="quantity" name="quantity" type="number" min="1" max="99999" required value="{{ old('quantity') }}" class="mt-1 block w-full" />
                        <x-input-error for="quantity" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="occurred_on" value="Fecha" />
                        <x-input id="occurred_on" name="occurred_on" type="date" max="{{ now()->toDateString() }}" required value="{{ old('occurred_on', now()->toDateString()) }}" class="mt-1 block w-full" />
                        <x-input-error for="occurred_on" class="mt-1" />
                    </div>

                    <div x-show="type === 'in'" x-cloak>
                        <x-label for="supplier" value="Proveedor" />
                        <x-input id="supplier" name="supplier" type="text" maxlength="120" value="{{ old('supplier') }}" x-bind:disabled="type !== 'in'" class="mt-1 block w-full" />
                        <x-input-error for="supplier" class="mt-1" />
                    </div>

                    <div x-show="type === 'out'" x-cloak>
                        <x-label for="reason" value="Motivo" />
                        <x-input id="reason" name="reason" type="text" maxlength="180" value="{{ old('reason') }}" x-bind:disabled="type !== 'out'" class="mt-1 block w-full" />
                        <x-input-error for="reason" class="mt-1" />
                    </div>

                    <div class="md:col-span-6">
                        <x-button>Registrar movimiento</x-button>
                    </div>
                </form>
            </section>

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
                                @can('manage-product')
                                    <th scope="col" class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($products as $product)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-gray-900">{{ $product->sku }}</td>
                                    <td class="px-4 py-3 text-gray-900">{{ $product->name }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $product->category }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900" data-stock-id="{{ $product->id }}">{{ $product->stock }}</td>
                                    @can('manage-product')
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-4">
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
                                            </div>
                                        </td>
                                    @endcan
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

            {{-- Movement history --}}
            <section aria-labelledby="history-title" class="overflow-hidden bg-white shadow sm:rounded-lg">
                <h3 id="history-title" class="p-4 text-lg font-medium text-gray-900">Historial de movimientos</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Producto</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium text-gray-500">Cantidad</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500">Proveedor / Motivo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($movements as $movement)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-900">{{ $movement->occurred_on->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-gray-900">
                                        <span class="font-mono">{{ $movement->product->sku }}</span>
                                        {{ $movement->product->name }}
                                        @if ($movement->product->trashed())
                                            <span class="text-xs text-gray-400">(archivado)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $movement->type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $movement->type === 'in' ? 'Entrada' : 'Salida' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-900">{{ $movement->quantity }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $movement->type === 'in' ? $movement->supplier : $movement->reason }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay movimientos que coincidan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4">
                    {{ $movements->links() }}
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
