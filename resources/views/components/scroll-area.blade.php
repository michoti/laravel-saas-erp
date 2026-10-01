@props(['label' => 'Scrollable content'])
<div tabindex="0" role="region" aria-label="{{ $label }}" {{ $attributes->class(['scroll-area overflow-y-auto overscroll-contain rounded-2xl']) }}>{{ $slot }}</div>
