@props(['tone' => 'primary'])
@php
$tones = [
    'primary' => 'bg-primary-soft text-primary-600',
    'neutral' => 'bg-surface-alt text-ink-soft ring-1 ring-steel-soft',
    'success' => 'bg-emerald-50 text-emerald-700',
][$tone];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-3 py-1 font-ui text-xs font-semibold', $tones]) }}>{{ $slot }}</span>
