@props(['current'])

@php($steps = ['Dane przelewu', 'Potwierdzenie', 'Wykonano'])

<div class="mb-6">
    <p class="text-sm text-stone-600 sm:hidden">Krok {{ $current }} z 3 · <strong class="text-emerald-950">{{ $steps[$current - 1] }}</strong></p>
    <div class="mt-2 flex gap-1.5 sm:hidden" aria-hidden="true">
        @foreach ($steps as $i => $step)
            <span class="h-1.5 flex-1 rounded-full {{ $i + 1 <= $current ? 'bg-emerald-700' : 'bg-stone-300' }}"></span>
        @endforeach
    </div>

    <ol aria-label="Kroki przelewu" class="hidden items-center gap-3 sm:flex">
        @foreach ($steps as $i => $step)
            @php($number = $i + 1)
            <li class="flex items-center gap-3 {{ $number < 3 ? 'flex-1' : '' }}" @if ($number === $current) aria-current="step" @endif>
                <span @class([
                    'flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                    'bg-emerald-700 text-white' => $number < $current,
                    'bg-emerald-950 text-white' => $number === $current,
                    'border-2 border-stone-500 text-stone-600' => $number > $current,
                ])>{{ $number < $current ? '✓' : $number }}</span>
                <span class="whitespace-nowrap text-sm {{ $number === $current ? 'font-semibold' : 'text-stone-600' }}">{{ $step }}</span>
                @if ($number < 3)
                    <span class="h-0.5 flex-1 {{ $number < $current ? 'bg-emerald-700' : 'bg-stone-300' }}" aria-hidden="true"></span>
                @endif
            </li>
        @endforeach
    </ol>
</div>
