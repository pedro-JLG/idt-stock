@if (session('success'))
    <div role="status" {{ $attributes->merge(['class' => 'rounded-md bg-green-50 p-4 text-sm text-green-800']) }}>
        {{ session('success') }}
    </div>
@endif
