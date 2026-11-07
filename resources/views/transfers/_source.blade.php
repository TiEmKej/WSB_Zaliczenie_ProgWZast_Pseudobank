<div class="card mb-4 flex flex-col gap-4 !p-5 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm text-stone-600">Przelew z konta</p>
        <p class="font-mono tracking-wide">{{ $user->formattedAccountNumber() }}</p>
    </div>
    <div class="sm:text-right">
        <p class="text-sm text-stone-600">Dostępne środki</p>
        <p class="text-2xl font-semibold tabular-nums">{{ number_format($user->balance, 2, ',', ' ') }} zł</p>
    </div>
</div>
