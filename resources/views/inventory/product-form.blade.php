<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $product->exists ? 'Editar producto' : 'Nuevo producto' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
                class="flex flex-col gap-6 bg-white p-6 shadow sm:rounded-lg"
            >
                @csrf
                @if ($product->exists)
                    @method('PUT')
                @endif

                <div>
                    <x-label for="name" value="Nombre" />
                    <x-input id="name" name="name" type="text" maxlength="120" required value="{{ old('name', $product->name) }}" class="mt-1 block w-full" />
                    <x-input-error for="name" class="mt-1" />
                </div>

                <div>
                    <x-label for="sku" value="SKU" />
                    <x-input id="sku" name="sku" type="text" maxlength="40" required value="{{ old('sku', $product->sku) }}" class="mt-1 block w-full font-mono" />
                    <x-input-error for="sku" class="mt-1" />
                </div>

                <div>
                    <x-label for="category" value="Categoría" />
                    <x-input id="category" name="category" type="text" maxlength="80" required value="{{ old('category', $product->category) }}" class="mt-1 block w-full" />
                    <x-input-error for="category" class="mt-1" />
                </div>

                <div>
                    <x-label for="description" value="Descripción" />
                    <textarea id="description" name="description" rows="4" maxlength="1000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $product->description) }}</textarea>
                    <x-input-error for="description" class="mt-1" />
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('stock.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">Cancelar</a>
                    <x-button>{{ $product->exists ? 'Guardar cambios' : 'Crear producto' }}</x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
