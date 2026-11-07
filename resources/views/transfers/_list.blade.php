@php
    $rows = $transfers->map(function ($transfer) use ($user) {
        $outgoing = $transfer->sender_id === $user->id;
        $other = $outgoing ? $transfer->recipient : $transfer->sender;

        return (object) [
            'transfer' => $transfer,
            'outgoing' => $outgoing,
            'other' => $other,
            'name' => $other?->name ?? 'konto usunięte',
            'amount' => ($outgoing ? '−' : '+').number_format($transfer->amount, 2, ',', ' ').' zł',
        ];
    });
@endphp

@if ($rows->isEmpty())
    <div class="card flex flex-col items-center gap-2 text-center">
        <p class="font-semibold">Brak przelewów.</p>
        <p class="text-sm text-stone-600">Gdy wykonasz pierwszy przelew, zobaczysz go tutaj.</p>
        <a href="{{ route('transfers.create') }}" class="btn btn-primary mt-3">Nowy przelew</a>
    </div>
@else
    <div class="table-wrap">
        <table class="hidden w-full sm:table">
            <thead>
                <tr>
                    <th class="th">Data</th>
                    <th class="th">Druga strona</th>
                    <th class="th">Tytuł</th>
                    <th class="th text-right">Kwota</th>
                    <th class="th"><span class="sr-only">Akcje</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="td whitespace-nowrap tabular-nums text-stone-600">{{ $row->transfer->created_at->format('d.m.Y H:i') }}</td>
                        <td class="td font-medium">{{ $row->name }}</td>
                        <td class="td">{{ $row->transfer->title }}</td>
                        <td class="td whitespace-nowrap text-right font-semibold tabular-nums {{ $row->outgoing ? 'text-red-700' : 'text-emerald-700' }}">{{ $row->amount }}</td>
                        <td class="td text-right">
                            @if ($row->outgoing && $row->other)
                                <form method="POST" action="{{ route('transfers.repeat', $row->transfer) }}">
                                    @csrf
                                    <button class="link-row">Powtórz</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <ul class="sm:hidden">
            @foreach ($rows as $row)
                <li class="mobile-row">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="font-semibold">{{ $row->name }}</span>
                        <span class="whitespace-nowrap font-semibold tabular-nums {{ $row->outgoing ? 'text-red-700' : 'text-emerald-700' }}">{{ $row->amount }}</span>
                    </div>
                    <p>{{ $row->transfer->title }}</p>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm tabular-nums text-stone-600">{{ $row->transfer->created_at->format('d.m.Y H:i') }}</span>
                        @if ($row->outgoing && $row->other)
                            <form method="POST" action="{{ route('transfers.repeat', $row->transfer) }}">
                                @csrf
                                <button class="link-row">Powtórz</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
