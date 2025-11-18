@props([
    'for' => null,
    'messages' => [],
])

@if ($for)
    @error($for)
        <p {{ $attributes->merge(['class' => 'text-sm text-red-600']) }}>{{ $message }}</p>
    @enderror
@else
    @foreach ((array) $messages as $message)
        <p {{ $attributes->merge(['class' => 'text-sm text-red-600']) }}>{{ $message }}</p>
    @endforeach
@endif
