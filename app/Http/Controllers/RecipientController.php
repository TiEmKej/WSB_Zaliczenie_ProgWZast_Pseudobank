<?php

namespace App\Http\Controllers;

use App\Models\Recipient;
use App\Support\AccountNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecipientController extends Controller
{
    public function index(Request $request)
    {
        return view('recipients.index', ['recipients' => $request->user()->recipients]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->merge(['account_number' => AccountNumber::normalize($request->input('account_number'))]);

        $data = $request->validate([
            'name' => ['required', 'regex:/^[\pL\d\s.\-]{2,100}$/u'],
            'account_number' => [
                'required',
                'regex:/^\d{26}$/',
                Rule::exists('users', 'account_number')->where('role', 'user'),
                Rule::notIn([$user->account_number]),
                Rule::unique('recipients')->where('user_id', $user->id),
            ],
        ], [
            'name.required' => 'Podaj nazwę odbiorcy.',
            'name.regex' => 'Nazwa odbiorcy ma od 2 do 100 znaków: litery, cyfry, spacje, kropki i myślniki.',
            'account_number.required' => 'Podaj numer konta odbiorcy.',
            'account_number.regex' => 'Numer konta musi składać się z 26 cyfr.',
            'account_number.exists' => 'Nie ma takiego konta odbiorcy.',
            'account_number.not_in' => 'Nie możesz dodać własnego konta do książki odbiorców.',
            'account_number.unique' => 'Ten odbiorca jest już w Twojej książce.',
        ]);

        $user->recipients()->create($data);

        return redirect()->route('recipients.index')->with('status', 'Odbiorca został zapisany.');
    }

    public function destroy(Request $request, Recipient $recipient)
    {
        abort_unless($recipient->user_id === $request->user()->id, 403);

        $recipient->delete();

        return redirect()->route('recipients.index')->with('status', 'Odbiorca został usunięty.');
    }
}
