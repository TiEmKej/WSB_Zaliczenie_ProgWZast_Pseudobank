<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Pesel;
use Illuminate\Http\Request;

class DevController extends Controller
{
    public function pesel(Request $request)
    {
        $request->validate(['gender' => ['nullable', 'in:K,M']]);

        $gender = $request->query('gender') ?: (random_int(0, 1) === 1 ? 'M' : 'K');

        do {
            $pesel = Pesel::generate($gender);
        } while (User::where('pesel', $pesel)->exists());

        return response()->json(['pesel' => $pesel, 'gender' => $gender]);
    }
}
