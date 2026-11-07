<div>
    <label for="gender" class="block">Płeć</label>
    <select id="gender" name="gender" class="w-full rounded border p-2">
        <option value="">Wybierz</option>
        @foreach (['K' => 'Kobieta', 'M' => 'Mężczyzna'] as $value => $label)
            <option value="{{ $value }}" @selected(old('gender', $user->gender() ?? null) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="pesel" class="block">PESEL</label>
    <div class="flex gap-2">
        <input id="pesel" name="pesel" value="{{ old('pesel', $user->pesel ?? '') }}" maxlength="11" inputmode="numeric" class="w-full rounded border p-2 font-mono">
        @unless (app()->isProduction())
            <button type="button" id="generate-pesel" class="whitespace-nowrap rounded border border-amber-400 bg-amber-50 px-3 text-sm text-amber-800 hover:bg-amber-100">Generuj poprawny PESEL</button>
        @endunless
    </div>
    @unless (app()->isProduction())
        <p class="text-sm text-slate-500">Narzędzie deweloperskie: losuje PESEL zgodny z wybraną płcią (albo wybiera płeć sam).</p>
    @endunless
</div>

@unless (app()->isProduction())
    <script>
        document.getElementById('generate-pesel').addEventListener('click', async () => {
            const gender = document.getElementById('gender');
            const response = await fetch(@json(route('dev.pesel')) + '?gender=' + encodeURIComponent(gender.value));
            const data = await response.json();

            document.getElementById('pesel').value = data.pesel;
            gender.value = data.gender;
        });
    </script>
@endunless
