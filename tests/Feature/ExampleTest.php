<?php

it('redirects the root path to the default language', function () {
    $response = $this->get('/');

    $response->assertRedirect('/th');
});
