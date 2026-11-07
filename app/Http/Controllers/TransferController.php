<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\User;
use App\Support\AccountNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransferController extends Controller
{
    private const MESSAGES = [
        'account_number.required' => 'Podaj numer konta odbiorcy.',
        'account_number.regex' => 'Numer konta musi składać się z 26 cyfr.',
        'account_number.exists' => 'Nie ma takiego konta odbiorcy.',
        'account_number.not_in' => 'Nie możesz wysłać przelewu na własne konto.',
        'amount.required' => 'Podaj kwotę.',
        'amount.numeric' => 'Kwota musi być liczbą.',
        'amount.gt' => 'Kwota musi być większa od zera.',
        'amount.regex' => 'Kwota może mieć maksymalnie 2 miejsca po kropce.',
        'title.required' => 'Podaj tytuł przelewu.',
        'title.regex' => 'Tytuł musi mieć od 3 do 140 znaków: litery, cyfry i podstawową interpunkcję.',
    ];

    public function index(Request $request)
    {
        return view('transfers.index', ['transfers' => $request->user()->transfers()->paginate(20)]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $recipient = $user->recipients()->find($request->query('recipient'));

        return view('transfers.create', [
            'recipients' => $user->recipients,
            'prefill' => $recipient?->account_number,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['account_number' => AccountNumber::normalize($request->input('account_number'))]);

        $data = $request->validate($this->rules($request->user()), self::MESSAGES);
        $data['save_recipient'] = $request->boolean('save_recipient');

        return $this->startTransfer($request->user(), $data);
    }

    public function repeat(Request $request, Transfer $transfer)
    {
        $user = $request->user();

        abort_unless($transfer->sender_id === $user->id, 403);

        if (! $transfer->recipient) {
            return back()->withErrors(['transfer' => 'Odbiorca tego przelewu nie istnieje.']);
        }

        $data = Validator::make([
            'account_number' => $transfer->recipient->account_number,
            'amount' => $transfer->amount,
            'title' => $transfer->title,
        ], $this->rules($user), self::MESSAGES)->validate();
        $data['save_recipient'] = false;

        return $this->startTransfer($user, $data);
    }

    public function confirm()
    {
        $pending = session('pending_transfer');

        if (! $pending) {
            return redirect()->route('transfers.create');
        }

        $recipient = User::where('account_number', $pending['account_number'])->first();

        return view('transfers.confirm', ['pending' => $pending, 'recipient' => $recipient]);
    }

    public function execute(Request $request)
    {
        $pending = session()->pull('pending_transfer');

        if (! $pending) {
            return redirect()->route('transfers.create');
        }

        $recipient = User::where('account_number', $pending['account_number'])->where('role', 'user')->first();

        if (! $recipient) {
            throw ValidationException::withMessages(['account_number' => 'Nie ma takiego konta odbiorcy.'])
                ->redirectTo(route('transfers.create'));
        }

        $sender = $request->user();

        DB::transaction(function () use ($sender, $recipient, $pending) {
            $accounts = User::whereIn('id', [$sender->id, $recipient->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($accounts[$sender->id]->balance < $pending['amount']) {
                throw ValidationException::withMessages(['amount' => 'Brak środków na koncie.'])
                    ->redirectTo(route('transfers.create'));
            }

            $accounts[$sender->id]->decrement('balance', $pending['amount']);
            $accounts[$recipient->id]->increment('balance', $pending['amount']);

            Transfer::create([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'amount' => $pending['amount'],
                'title' => $pending['title'],
            ]);
        });

        if ($pending['save_recipient'] ?? false) {
            $sender->recipients()->firstOrCreate(
                ['account_number' => $recipient->account_number],
                ['name' => $recipient->name],
            );
        }

        return redirect()->route('dashboard')->with('status', 'Przelew został wykonany.');
    }

    private function rules(User $user): array
    {
        return [
            'account_number' => [
                'required',
                'regex:/^\d{26}$/',
                Rule::exists('users', 'account_number')->where('role', 'user'),
                Rule::notIn([$user->account_number]),
            ],
            'amount' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/', 'numeric', 'gt:0'],
            'title' => ['required', 'string', 'regex:/^[\pL\d\s.,\-]{3,140}$/u'],
        ];
    }

    private function startTransfer(User $user, array $data)
    {
        if ($user->fresh()->balance < $data['amount']) {
            return back()->withErrors(['amount' => 'Brak środków na koncie.'])->withInput();
        }

        session(['pending_transfer' => $data]);

        return redirect()->route('transfers.confirm');
    }
}
