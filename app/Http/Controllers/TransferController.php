<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        return view('transfers.index', ['transfers' => $request->user()->transfers()->paginate(20)]);
    }

    public function create()
    {
        return view('transfers.create');
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'account_number' => [
                'required',
                Rule::exists('users', 'account_number')->where('role', 'user'),
                Rule::notIn([$user->account_number]),
            ],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'title' => ['required', 'string', 'max:140'],
        ], [
            'account_number.required' => 'Podaj numer konta odbiorcy.',
            'account_number.exists' => 'Nie ma takiego konta odbiorcy.',
            'account_number.not_in' => 'Nie możesz wysłać przelewu na własne konto.',
            'amount.required' => 'Podaj kwotę.',
            'amount.numeric' => 'Kwota musi być liczbą.',
            'amount.gt' => 'Kwota musi być większa od zera.',
            'amount.max' => 'Kwota jest zbyt duża.',
            'title.required' => 'Podaj tytuł przelewu.',
            'title.max' => 'Tytuł może mieć najwyżej 140 znaków.',
        ]);

        if ($user->fresh()->balance < $data['amount']) {
            return back()->withErrors(['amount' => 'Brak środków na koncie.'])->withInput();
        }

        session(['pending_transfer' => $data]);

        return redirect()->route('transfers.confirm');
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

        return redirect()->route('dashboard')->with('status', 'Przelew został wykonany.');
    }
}
