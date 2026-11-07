@props(['name', 'label', 'type' => 'text', 'value' => null, 'mono' => false, 'hint' => null, 'suffix' => null, 'hideLabel' => false, 'placeholder' => null, 'maxlength' => null, 'inputmode' => null])

@php
    $error = $errors->first($name);
    $describedBy = trim(($error ? $name.'-error ' : '').($hint ? $name.'-hint' : ''));
@endphp

<div class="space-y-1.5">
    <label for="{{ $name }}" class="{{ $hideLabel ? 'sr-only' : 'block text-sm font-medium' }}">{{ $label }}</label>
    <div class="relative">
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if ($type !== 'password') value="{{ $value }}" @endif
               @if ($placeholder) placeholder="{{ $placeholder }}" @endif
               @if ($maxlength) maxlength="{{ $maxlength }}" @endif
               @if ($inputmode) inputmode="{{ $inputmode }}" @endif
               @if ($error) aria-invalid="true" @endif
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               @class(['field', 'field-error' => $error, 'font-mono tracking-wide' => $mono, 'pr-11 text-right tabular-nums' => $suffix])>
        @if ($suffix)
            <span class="pointer-events-none absolute inset-y-0 right-3.5 flex items-center text-stone-600" aria-hidden="true">{{ $suffix }}</span>
        @endif
    </div>
    @if ($hint)
        <p id="{{ $name }}-hint" class="text-sm text-stone-600">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $name }}-error" class="flex items-start gap-1.5 text-sm leading-snug text-red-700">
            <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="10" cy="10" r="8"/><path d="M10 6v5M10 14h.01"/></svg>
            {{ $error }}
        </p>
    @endif
</div>
