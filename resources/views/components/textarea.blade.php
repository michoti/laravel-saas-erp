@props(['label', 'name', 'rows' => 4])
@php $error = $errors->first($name); @endphp
<div>
    <label for="f-{{ $name }}" class="mb-1.5 block font-ui text-sm font-semibold text-ink">{{ $label }}</label>
    <textarea id="f-{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
        {{ $attributes->class(['w-full rounded-xl border-0 bg-surface-alt px-4 py-3 text-base text-ink shadow-inset ring-1 transition placeholder:text-steel focus:bg-surface focus:ring-2 focus:ring-primary', $error ? 'ring-danger' : 'ring-steel-soft']) }}
        @if ($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif></textarea>
    @if ($error)<p id="{{ $name }}-error" role="alert" class="mt-1 text-sm text-danger">{{ $error }}</p>@endif
</div>
