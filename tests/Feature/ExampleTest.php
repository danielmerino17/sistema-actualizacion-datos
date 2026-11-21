<?php

use Inertia\Testing\AssertableInertia as Assert;

test('la pantalla de inicio de sesion responde correctamente', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Login')
        );
});
