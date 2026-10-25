<?php

it('renders the public homepage', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('<html lang="fa" dir="rtl">', false)
        ->assertSee('کلینیک دندانپزشکی دیجیتال — دکتر آرش کوهفر', false);
});
