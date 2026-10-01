{{-- Bind with x-model. Expects the parent Alpine scope to expose `fill` (0–100%). --}}
@props(['label', 'min' => 1, 'max' => 50, 'step' => 1])
<div>
    <label class="mb-3 flex items-baseline justify-between font-ui text-sm font-semibold">
        <span>{{ $label }}</span>
        <span class="text-2xl font-bold text-primary" x-text="users"></span>
    </label>
    <input type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" {{ $attributes->class('range') }} x-bind:style="`--fill:${fill}`" aria-label="{{ $label }}">
    <div class="mt-2 flex justify-between text-xs text-ink-soft"><span>{{ $min }}</span><span>{{ $max }}+</span></div>
</div>
