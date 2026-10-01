@props(['variant' => 'primary', 'href' => null])
@php
$base = 'inline-flex select-none min-h-12 min-w-12 items-center justify-center gap-2 rounded-full px-6 font-ui text-sm font-semibold transition duration-200 active:scale-[.98] disabled:pointer-events-none disabled:opacity-60';
$styles = [
    'primary' => 'bg-primary text-white shadow-soft hover:bg-primary-600 hover:shadow-soft-lg',
    'secondary' => 'bg-surface text-ink shadow-soft ring-1 ring-steel-soft hover:ring-steel',
    'ghost' => 'text-ink hover:bg-primary-soft',
][$variant];
@endphp
@if ($href)
    <a href="{{ $href }}" data-squash {{ $attributes->class([$base, $styles]) }}>{{ $slot }}</a>
@else
    <button data-squash {{ $attributes->merge(['type' => 'button'])->class([$base, $styles]) }}>{{ $slot }}</button>
@endif
