@props(['name', 'label', 'options', 'selected' => null, 'placeholder' => null])

@php($error = $errors->first($name))

<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <div class="relative">
        <select id="{{ $name }}" name="{{ $name }}"
                @if ($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
                @class(['field appearance-none pr-10', 'field-error' => $error])>
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $value => $text)
                <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
            @endforeach
        </select>
        <svg class="pointer-events-none absolute right-3.5 top-1/2 size-4 -translate-y-1/2 text-stone-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
    </div>
    @if ($error)
        <p id="{{ $name }}-error" class="flex items-start gap-1.5 text-sm leading-snug text-red-700">
            <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="10" cy="10" r="8"/><path d="M10 6v5M10 14h.01"/></svg>
            {{ $error }}
        </p>
    @endif
</div>
