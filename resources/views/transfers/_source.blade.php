<div class="mb-4 rounded bg-white p-4 shadow">
    <p class="text-sm text-slate-500">Przelew z konta</p>
    <p class="font-mono">{{ $user->formattedAccountNumber() }}</p>
    <p class="mt-2 text-sm text-slate-500">Dostępne środki</p>
    <p class="text-2xl font-semibold">{{ number_format($user->balance, 2, ',', ' ') }} zł</p>
</div>
