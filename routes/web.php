<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });


// Public marketing site. Views are grouped by feature under resources/views/domains/.
Route::view('/', 'domains.marketing.home')->name('home');
Route::view('/apps', 'domains.marketing.apps')->name('apps');
Route::view('/pricing', 'domains.marketing.pricing')->name('pricing');
Route::view('/about', 'domains.marketing.about')->name('about');
Route::view('/community', 'domains.community.index')->name('community');
Route::view('/docs', 'domains.community.docs')->name('docs');

// SEO: dynamic sitemap and robots (delete public/robots.txt if present). Register on central domains only.
Route::get('/sitemap.xml', function () {
    $urls = collect(['home', 'apps', 'pricing', 'about', 'community', 'docs'])
        ->map(fn ($r) => '<url><loc>'.e(route($r)).'</loc><lastmod>'.now()->toDateString().'</lastmod><priority>'.($r === 'home' ? '1.0' : '0.8').'</priority></url>')
        ->implode('');

    return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$urls.'</urlset>', 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::get('/robots.txt', fn () => response("User-agent: *\nAllow: /\nDisallow: /app\nDisallow: /admin\nDisallow: /livewire\n\nSitemap: ".route('sitemap')."\n", 200, ['Content-Type' => 'text/plain']))->name('robots');
