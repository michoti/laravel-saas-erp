{{-- Works with wire:model. Shows the Laravel validation error right beside the field. --}}
@props(['label', 'name', 'type' => 'text', 'hint' => null])
@php $error = $errors->first($name); @endphp
<div>
    <label for="f-{{ $name }}" class="mb-1.5 block font-ui text-sm font-semibold text-ink">{{ $label }}</label>
    <input id="f-{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        {{ $attributes->class(['min-h-12 w-full rounded-xl border-0 bg-surface-alt px-4 text-base text-ink shadow-inset ring-1 transition placeholder:text-steel focus:bg-surface focus:ring-2 focus:ring-primary', $error ? 'ring-danger' : 'ring-steel-soft']) }}
        @if ($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>
    @if ($hint && ! $error)<p class="mt-1 text-xs text-ink-soft">{{ $hint }}</p>@endif
    @if ($error)<p id="{{ $name }}-error" role="alert" class="mt-1 text-sm text-danger">{{ $error }}</p>@endif
</div>
