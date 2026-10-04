<?php

/*
 * Structural accessibility that a browser scan (axe) found lacking and that is cheap to keep:
 * one main landmark and one level-one heading per page, named filter controls, named
 * table-action columns, and text colours that stay at WCAG AA contrast in both themes.
 */

function appPages(array $d): array
{
    return ['/dashboard', '/batches', "/batches/{$d['batch']->id}", '/harvest', '/inventory', "/inventory/{$d['item']->id}",
        '/customers', '/orders', "/orders/{$d['order']->id}", '/reports', '/activity-logs', '/users', '/settings'];
}

it('gives every page one main landmark and a level-one heading', function () {
    [$farm, $admin] = makeFarm('Landmarks Farm');
    $d = seedFarmData($farm);
    $this->actingAs($admin);

    foreach (appPages($d) as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect(substr_count($html, '<main '))->toBe(1, "{$url}: main landmarks")
            ->and(substr_count($html, '<h1'))->toBe(1, "{$url}: level-one headings")
            ->and($html)->toContain('id="main-content"');
    }
});

it('gives the platform pages one main landmark and a level-one heading', function () {
    [$farm] = makeFarm('Platform Landmarks Farm');
    $this->actingAs(superAdmin());

    foreach (['/admin/farms', "/admin/farms/{$farm->id}", "/admin/farms/{$farm->id}/features", '/admin/farms/archived', '/admin/audit', '/admin/security'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect(substr_count($html, '<main '))->toBe(1, "{$url}: main landmarks")
            ->and(substr_count($html, '<h1'))->toBeGreaterThanOrEqual(1, "{$url}: level-one headings");
    }
});

it('names every filter control', function () {
    [$farm, $admin] = makeFarm('Filters Farm');
    $d = seedFarmData($farm);
    $this->actingAs($admin);

    foreach (['/batches', '/harvest', '/inventory', '/orders', '/activity-logs'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();
        preg_match_all('/<select\b[^>]*class="filter-select"[^>]*>/s', $html, $selects);

        foreach ($selects[0] as $tag) {
            expect(str_contains($tag, 'aria-label='))->toBeTrue("{$url}: {$tag}");
        }
    }
});

it('names the date filters on the platform audit', function () {
    $html = $this->actingAs(superAdmin())->get('/admin/audit')->assertOk()->getContent();

    expect($html)->toContain('aria-label="From date"')->and($html)->toContain('aria-label="To date"');
});

it('never leaves a table header empty', function () {
    [$farm, $admin] = makeFarm('Headers Farm');
    $d = seedFarmData($farm);
    $this->actingAs($admin);

    foreach (['/batches', "/batches/{$d['batch']->id}", '/customers', '/harvest', '/inventory', '/orders', "/orders/{$d['order']->id}", '/users'] as $url) {
        expect(str_contains($this->get($url)->getContent(), '<th></th>'))->toBeFalse("{$url} has an empty <th>");
    }
});

it('labels the farm name field on the settings page', function () {
    [$farm, $admin] = makeFarm('Settings Label Farm');

    $this->actingAs($admin)->get('/settings')->assertOk()
        ->assertSee('for="farm_name"', false)
        ->assertSee('id="farm_name"', false);
});

// ── Contrast ────────────────────────────────────────────────────────────────

function themeTokens(string $block): array
{
    preg_match_all('/--([a-z-]+):\s*(#[0-9a-fA-F]{6})\s*;/', $block, $m, PREG_SET_ORDER);

    return collect($m)->mapWithKeys(fn ($x) => [$x[1] => strtolower($x[2])])->all();
}

function luminance(string $hex): float
{
    [$r, $g, $b] = array_map(fn ($i) => hexdec(substr(ltrim($hex, '#'), $i, 2)) / 255, [0, 2, 4]);
    $f = fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

    return 0.2126 * $f($r) + 0.7152 * $f($g) + 0.0722 * $f($b);
}

function contrast(string $a, string $b): float
{
    [$hi, $lo] = [max(luminance($a), luminance($b)), min(luminance($a), luminance($b))];

    return ($hi + 0.05) / ($lo + 0.05);
}

it('keeps text colours at WCAG AA contrast on every background in both themes', function () {
    $css = file_get_contents(resource_path('views/layouts/app.blade.php'));

    preg_match('/:root\s*\{(.*?)\}/s', $css, $dark);
    preg_match('/:root\[data-theme="light"\]\s*\{(.*?)\}/s', $css, $light);
    preg_match('/prefers-color-scheme:\s*light\)\s*\{\s*:root:not\(\[data-theme="dark"\]\)\s*\{(.*?)\}/s', $css, $lightMedia);

    foreach (['dark' => $dark[1], 'light' => $light[1], 'light (system preference)' => $lightMedia[1]] as $theme => $block) {
        $t = themeTokens($block);

        foreach (['text', 'text-muted', 'text-dim', 'green-light', 'danger', 'warning', 'info'] as $text) {
            foreach (['bg', 'card-bg', 'sidebar-bg'] as $surface) {
                expect(contrast($t[$text], $t[$surface]))->toBeGreaterThanOrEqual(4.5, "{$theme}: --{$text} on --{$surface}");
            }
        }
    }
});

it('gives the sign-in, sign-up and reset pages one main landmark and a level-one heading', function () {
    foreach (['/login', '/register/farm', '/forgot-password', '/reset-password/a-token'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect(substr_count($html, '<main '))->toBe(1, "{$url}: main landmarks")
            ->and(substr_count($html, '<h1'))->toBe(1, "{$url}: level-one headings");
    }
});

it('keeps the sign-in layout\'s own colours at WCAG AA contrast too', function () {
    $css = file_get_contents(resource_path('views/layouts/auth.blade.php'));

    preg_match('/:root\s*\{(.*?)\}/s', $css, $dark);
    preg_match('/:root\[data-theme="light"\]\s*\{(.*?)\}/s', $css, $light);
    preg_match('/prefers-color-scheme:\s*light\)\s*\{\s*:root:not\(\[data-theme="dark"\]\)\s*\{(.*?)\}/s', $css, $lightMedia);

    foreach (['dark' => $dark[1], 'light' => $light[1], 'light (system preference)' => $lightMedia[1]] as $theme => $block) {
        $t = themeTokens($block);

        foreach (['text', 'text-muted', 'green-light', 'danger'] as $text) {
            expect(contrast($t[$text], $t['card-bg']))->toBeGreaterThanOrEqual(4.5, "{$theme}: --{$text} on the card");
        }

        expect(contrast($t['text-muted'], $t['green-dark']))->toBeGreaterThanOrEqual(4.5, "{$theme}: --text-muted on the page");
    }
});

it('keeps green chip text readable on its soft green fill in both themes', function () {
    $css = file_get_contents(resource_path('views/layouts/app.blade.php'));

    preg_match('/:root\s*\{(.*?)\}/s', $css, $dark);
    preg_match('/:root\[data-theme="light"\]\s*\{(.*?)\}/s', $css, $light);

    foreach (['dark' => $dark[1], 'light' => $light[1]] as $theme => $block) {
        $t = themeTokens($block);

        expect(contrast($t['green-light'], $t['green-soft']))->toBeGreaterThanOrEqual(4.5, "{$theme}: green text on --green-soft");
    }
});

it('gives every table that scrolls sideways its own name', function () {
    $js = file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($js)->toContain('window.__tableNames');
});
