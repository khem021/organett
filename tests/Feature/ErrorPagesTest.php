<?php

use Illuminate\Support\Facades\Route;

/*
 * Every error a visitor can meet shows a branded page that says what happened and
 * offers a way forward, and never exposes an exception message on a server error.
 */

it('shows a friendly 404 for an address that does not exist', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('Back to dashboard');
});

it('shows a friendly 404 for a signed-in user too', function () {
    [$farm, $admin] = makeFarm('Lost Farm');

    $this->actingAs($admin)->get('/batches/999999')->assertNotFound()->assertSee('Page not found');
});

it('gives the real reason on a 403 instead of one fixed sentence', function () {
    [$farm, $staff] = makeFarm('Reason Farm', 'farm_staff', ['reports' => false]);

    $this->actingAs($staff)->get('/reports')->assertForbidden()
        ->assertSee("The 'reports' feature is not enabled for your farm.")
        ->assertDontSee('This area is for administrators only');

    $this->get('/users')->assertForbidden()->assertSee('restricted to administrators only');
});

it('explains a read-only view on a blocked write', function () {
    [$farm] = makeFarm('Read Only Reason Farm');
    $this->actingAs(superAdmin())->post("/admin/farms/{$farm->id}/impersonate");

    $this->post('/customers', ['customer_name' => 'x', 'phone' => '0917', 'address' => 'x'])
        ->assertForbidden()
        ->assertSee('This view is read-only');
});

it('shows a clear page when a form has timed out, with a way back', function () {
    Route::get('/_errors/419', fn () => abort(419));

    $this->get('/_errors/419')->assertStatus(419)
        ->assertSee('Your session timed out')
        ->assertSee('nothing was saved')
        ->assertSee('Go back and try again');
});

it('shows a clear page for too many requests', function () {
    Route::get('/_errors/429', fn () => abort(429));

    $this->get('/_errors/429')->assertStatus(429)->assertSee('Too many requests');
});

it('shows a clear page while the site is being updated', function () {
    Route::get('/_errors/503', fn () => abort(503));

    $this->get('/_errors/503')->assertStatus(503)->assertSee('Back in a few minutes');
});

it('shows a calm 500 page that does not leak the exception', function () {
    config(['app.debug' => false]);
    Route::get('/_errors/500', fn () => throw new RuntimeException('secret database detail'));

    $this->get('/_errors/500')->assertStatus(500)
        ->assertSee('Something went wrong on our side')
        ->assertDontSee('secret database detail');
});

it('serves error pages that need nothing but themselves', function () {
    // No layout, session or asset build: an error page must still render when those are what failed.
    $html = $this->get('/no-such-page')->getContent();

    expect($html)->not->toContain('/build/assets')
        ->and($html)->toContain('prefers-color-scheme')
        ->and($html)->toContain('<main>');
});
