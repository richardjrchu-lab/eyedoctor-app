<?php

test('guest is redirected to login from doctor workspace', function () {
    $response = $this->get('/');

    $response->assertRedirect(
        route('login', absolute: false)
    );
});
