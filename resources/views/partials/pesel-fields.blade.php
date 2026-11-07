<x-select name="gender" label="Płeć" placeholder="Wybierz" :options="['K' => 'Kobieta', 'M' => 'Mężczyzna']" :selected="old('gender', $user->gender() ?? null)" />
<x-field name="pesel" label="PESEL" mono :value="old('pesel', $user->pesel ?? '')" maxlength="11" inputmode="numeric" />

@unless (app()->isProduction())
    <div class="alert alert-warning sm:col-span-2">
        <div class="space-y-2">
            <p class="font-semibold">Narzędzie deweloperskie</p>
            <button type="button" id="generate-pesel" class="btn min-h-11 border border-amber-700 bg-white text-amber-900 hover:bg-amber-100">Generuj poprawny PESEL</button>
            <p class="text-sm">Narzędzie deweloperskie: losuje PESEL zgodny z wybraną płcią (albo wybiera płeć sam).</p>
        </div>
    </div>

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
