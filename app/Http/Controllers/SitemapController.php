<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            [
                'loc' => url('/buscar'),
                'changefreq' => 'daily',
                'priority' => '0.90',
            ],
            [
                'loc' => url('/login'),
                'changefreq' => 'monthly',
                'priority' => '0.40',
            ],
            [
                'loc' => url('/forgot-password'),
                'changefreq' => 'yearly',
                'priority' => '0.20',
            ],
        ];

        if (Route::has('register')) {
            $urls[] = [
                'loc' => url('/register'),
                'changefreq' => 'monthly',
                'priority' => '0.30',
            ];
        }

        return response()
            ->view('sitemap.index', [
                'urls' => $urls,
                'lastmod' => now()->toDateString(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
