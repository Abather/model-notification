<?php

namespace Abather\ModelNotification;

use Abather\ModelNotification\Contracts\TemplateRepositoryInterface;
use Abather\ModelNotification\Contracts\TemplateCacheInterface;
use Abather\ModelNotification\Repositories\TemplateRepository;
use Abather\ModelNotification\Cache\TemplateCache;
use Abather\ModelNotification\Services\TemplateService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModelNotificationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('model-notification')
            ->hasConfigFile()
            ->hasMigration('create_model_notification_table');
    }

    public function registeringPackage(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(TemplateRepositoryInterface::class, TemplateRepository::class);
        $this->app->bind(TemplateCacheInterface::class, function ($app) {
            return new TemplateCache(
                $app->make(TemplateRepositoryInterface::class)
            );
        });
        $this->app->singleton(TemplateService::class, function ($app) {
            return new TemplateService(
                $app->make(TemplateCacheInterface::class)
            );
        });
    }
}
