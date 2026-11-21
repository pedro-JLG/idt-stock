<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Movimientos
            </h2>

            <a href="{{ route('movements.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Registrar movimiento
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-8">
            <x-flash-message />

            {{-- History filters --}}
            <form method="GET" action="{{ route('movements.index') }}" class="grid grid-cols-1 items-start gap-4 bg-white p-4 shadow sm:rounded-lg md:grid-cols-3">
                @if ($filteredProduct)
                    <input type="hidden" name="product" value="{{ $filteredProduct->id }}">
                @endif

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

                <div class="flex flex-wrap items-center gap-3 md:col-span-3">
                    <x-button>Filtrar</x-button>
                    <a href="{{ route('movements.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">Limpiar filtros</a>

                    @if ($filteredProduct)
                        <span class="text-sm text-gray-600">
                            Producto: <span class="font-mono">{{ $filteredProduct->sku }}</span> {{ $filteredProduct->name }}
                            <a href="{{ route('movements.index', request()->except(['product', 'page'])) }}" class="ms-2 text-indigo-600 underline hover:text-indigo-800">Quitar filtro de producto</a>
                        </span>
                    @endif
                </div>
            </form>

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
                                        <a href="{{ route('stock.index', ['q' => $movement->product->sku]) }}" class="hover:text-indigo-700 hover:underline">
                                            <span class="font-mono">{{ $movement->product->sku }}</span>
                                            {{ $movement->product->name }}
                                        </a>
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
</x-app-layout>
