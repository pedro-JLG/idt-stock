<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Registrar movimiento
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <section aria-labelledby="movement-title" class="bg-white p-6 shadow sm:rounded-lg">
                <h3 id="movement-title" class="sr-only">Nuevo movimiento</h3>

                <form
                    method="POST"
                    action="{{ route('movements.store') }}"
                    x-data="{ type: @js(old('type', $selectedType)) }"
                    class="flex flex-col gap-5"
                >
                    @csrf

                    <div>
                        <x-label for="movement_type" value="Tipo" />
                        <select id="movement_type" name="type" x-model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="in">Entrada</option>
                            <option value="out">Salida</option>
                        </select>
                        <x-input-error for="type" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="product_id" value="Producto" />
                        <select id="product_id" name="product_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Seleccioná un producto</option>
                            @foreach ($options as $option)
                                <option value="{{ $option->id }}" @selected((string) old('product_id', $selectedProductId) === (string) $option->id)>
                                    {{ $option->sku }} · {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error for="product_id" class="mt-1" />
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

                    <div class="flex items-center gap-4 pt-1">
                        <x-button>Registrar movimiento</x-button>
                        <a href="{{ route('movements.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">Cancelar</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
