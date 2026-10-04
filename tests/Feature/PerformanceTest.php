<?php

/*
 * Things that make a page feel slow or jump about while it loads.
 */

it('keeps the logo small enough for a login page', function () {
    // It shows at 190px; 1024px and 1.7 MB was a lot to download before anyone could sign in.
    [$width, $height] = getimagesize(public_path('logo-mushroom.png'));

    expect(filesize(public_path('logo-mushroom.png')))->toBeLessThan(300 * 1024)
        ->and($width)->toBeLessThanOrEqual(512)
        ->and($height)->toBeLessThanOrEqual(512);
});

it('gives every image on a page a width and a height so nothing jumps when it loads', function () {
    [$farm, $admin] = makeFarm('Image Size Farm');
    $d = seedFarmData($farm);

    // Tags inside JavaScript templates (the avatar preview) fill a fixed-size box and cannot shift the page.
    $sized = fn (string $tag): bool => str_contains($tag, '${')
        || (preg_match('/\bwidth="\d+"/', $tag) === 1 && preg_match('/\bheight="\d+"/', $tag) === 1);

    foreach (['/login', '/register/farm', '/forgot-password', '/reset-password/x'] as $url) {
        preg_match_all('/<img\b[^>]*>/s', $this->get($url)->getContent(), $tags);

        foreach ($tags[0] as $tag) {
            expect($sized($tag))->toBeTrue("{$url}: {$tag}");
        }
    }

    $this->actingAs($admin);

    foreach (['/dashboard', '/users', "/orders/{$d['order']->id}/print"] as $url) {
        preg_match_all('/<img\b[^>]*>/s', $this->get($url)->getContent(), $tags);

        foreach ($tags[0] as $tag) {
            expect($sized($tag))->toBeTrue("{$url}: {$tag}");
        }
    }
});

it('lets text show in a fallback font while the web font loads', function () {
    foreach (['layouts/app', 'layouts/auth', 'orders/print'] as $view) {
        expect(file_get_contents(resource_path("views/{$view}.blade.php")))->toContain('&display=swap');
    }
});
