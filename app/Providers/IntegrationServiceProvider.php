<?php

namespace App\Providers;

use App\Contracts\AIProvider;
use App\Contracts\CentreDataImporter;
use App\Contracts\DirectionsProvider;
use App\Contracts\NotificationProvider;
use App\Contracts\PlacesProvider;
use App\Contracts\TransportProvider;
use App\Integrations\Ai\GroundedAssistantProvider;
use App\Integrations\Ai\OpenAiProvider;
use App\Integrations\Directions\GoogleDirectionsProvider;
use App\Integrations\Import\CsvCentreDataImporter;
use App\Integrations\Notifications\LogNotificationProvider;
use App\Integrations\Notifications\MailNotificationProvider;
use App\Integrations\Places\CuratedPlacesProvider;
use App\Integrations\Places\GooglePlacesProvider;
use App\Integrations\Transport\EstimatedTransportProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Binds every external integration behind a contract.
 *
 * Application code depends only on the interfaces, so:
 *  - swapping a vendor is a one-line configuration change, and
 *  - a missing credential degrades to a documented fallback instead of breaking a
 *    feature (and never silently substitutes fake production data).
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GroundedAssistantProvider::class);
        $this->app->singleton(OpenAiProvider::class);

        $this->app->singleton(AIProvider::class, function ($app) {
            return config('services.ai.provider') === 'openai'
                ? $app->make(OpenAiProvider::class)
                : $app->make(GroundedAssistantProvider::class);
        });

        $this->app->singleton(DirectionsProvider::class, GoogleDirectionsProvider::class);

        $this->app->singleton(PlacesProvider::class, function ($app) {
            return match (config('services.places.provider')) {
                'google' => $app->make(GooglePlacesProvider::class),
                default => $app->make(CuratedPlacesProvider::class),
            };
        });

        // Only one transport implementation exists today. The contract is bound (not
        // the class) so an authorised partner API can be added without touching
        // controllers, the journey planner or the tests.
        $this->app->singleton(TransportProvider::class, EstimatedTransportProvider::class);

        $this->app->singleton(CentreDataImporter::class, CsvCentreDataImporter::class);

        $this->app->singleton(NotificationProvider::class, function ($app) {
            return config('services.notifications.provider') === 'log'
                || in_array(config('mail.default'), ['log', 'array', null], true)
                    ? $app->make(LogNotificationProvider::class)
                    : $app->make(MailNotificationProvider::class);
        });
    }
}
