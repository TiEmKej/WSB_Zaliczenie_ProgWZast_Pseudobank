<table class="w-full rounded bg-white shadow">
    <thead>
        <tr class="border-b text-left">
            <th class="p-3">Data</th>
            <th class="p-3">Druga strona</th>
            <th class="p-3">Tytuł</th>
            <th class="p-3 text-right">Kwota</th>
            <th class="p-3"></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transfers as $transfer)
            @php
                $outgoing = $transfer->sender_id === $user->id;
                $other = $outgoing ? $transfer->recipient : $transfer->sender;
            @endphp
            <tr class="border-b">
                <td class="p-3">{{ $transfer->created_at->format('d.m.Y H:i') }}</td>
                <td class="p-3">{{ $other?->name ?? 'konto usunięte' }}</td>
                <td class="p-3">{{ $transfer->title }}</td>
                <td class="p-3 text-right {{ $outgoing ? 'text-red-600' : 'text-green-600' }}">
                    {{ $outgoing ? '−' : '+' }}{{ number_format($transfer->amount, 2, ',', ' ') }} zł
                </td>
                <td class="p-3 text-right">
                    @if ($outgoing && $other)
                        <form method="POST" action="{{ route('transfers.repeat', $transfer) }}">
                            @csrf
                            <button class="text-blue-600 hover:underline">Powtórz</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="p-3 text-center text-slate-500">Brak przelewów.</td></tr>
        @endforelse
    </tbody>
</table>
