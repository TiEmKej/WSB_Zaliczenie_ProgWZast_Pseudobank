<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_strona_startowa_sie_wyswietla(): void
    {
        $this->withoutVite();

        $this->get('/')->assertOk()->assertSee('Pseudobank');
    }
}
