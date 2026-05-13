<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class PwaServiceWorkerController extends Controller
{
    public function __invoke(): Response
    {
        $version = (string) config('app.version', '1.0.0');
        $serviceWorker = file_get_contents(public_path('service-worker.js'));
        $serviceWorker = str_replace('__AQATENDE_APP_VERSION__', $version, $serviceWorker);

        return response($serviceWorker, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
