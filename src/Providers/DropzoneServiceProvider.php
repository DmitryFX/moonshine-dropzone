<?php

declare(strict_types=1);

namespace MoonShine\Dropzone\Providers;

use MoonShine\Dropzone\Http\Controllers\DropzoneController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MoonShine\Laravel\Http\Middleware\Authenticate;

final class DropzoneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'moonshine-dropzone');

        Route::post('moonshine-dropzone', [ DropzoneController::class, 'dropzone'] )
           ->middleware(['moonshine', Authenticate::class])
           ->name('moonshine-dropzone');

        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/moonshine-dropzone'),
        ], ['moonshine-dropzone-assets', 'laravel-assets']);
    }
}
