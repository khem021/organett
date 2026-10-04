<?php

/*
 * The sign-in, sign-up and reset forms have to carry every field the controllers
 * validate, and each password field has to offer a show/hide control.
 */

/** @return list<string> the name="..." of every <input> on the page */
function inputNames(string $html): array
{
    preg_match_all('/<input\b[^>]*\bname="([^"]+)"/s', $html, $m);

    return $m[1];
}

it('gives the sign-in form its email and password fields', function () {
    $html = $this->get('/login')->assertOk()->getContent();

    expect(inputNames($html))->toContain('email', 'password', 'remember')
        ->and(substr_count($html, 'data-pw-toggle="password"'))->toBe(1);
});

it('gives the farm sign-up form every field it validates', function () {
    $html = $this->get('/register/farm')->assertOk()->getContent();

    expect(inputNames($html))->toContain('farm_name', 'full_name', 'email', 'password', 'password_confirmation')
        ->and(substr_count($html, 'data-pw-toggle='))->toBe(2);
});

it('gives the reset form its token, email and both password fields', function () {
    $html = $this->get('/reset-password/a-token?email=owner@example.test')->assertOk()->getContent();

    expect(inputNames($html))->toContain('token', 'email', 'password', 'password_confirmation')
        ->and(substr_count($html, 'data-pw-toggle='))->toBe(2);
});

it('keeps the email on the reset form as the type of input a browser can autofill', function () {
    $html = $this->get('/reset-password/a-token')->assertOk()->getContent();

    expect(preg_match('/<input\b[^>]*type="email"[^>]*name="email"|<input\b[^>]*name="email"[^>]*type="email"/s', $html))->toBe(1);
});

it('gives the forgot-password form its email field', function () {
    expect(inputNames($this->get('/forgot-password')->assertOk()->getContent()))->toContain('email');
});

it('ties a field error to its password input for screen readers', function () {
    $html = $this->from('/register/farm')->followingRedirects()
        ->post('/register/farm', ['farm_name' => 'x', 'full_name' => 'x', 'email' => 'x@example.test', 'password' => 'short', 'password_confirmation' => 'short'])
        ->getContent();

    expect($html)->toContain('aria-invalid="true"')
        ->and($html)->toMatch('/aria-describedby="password-error[ "]/')
        ->and($html)->toContain('id="password-error"');
});
