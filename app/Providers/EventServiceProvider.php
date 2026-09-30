<?php

namespace App\Providers;

use App\Events\Notifications\ChannelManagerChanged;
use App\Events\Notifications\ChannelsAnalyticsStopped;
use App\Events\Notifications\ChannelsBonusCalculated;
use App\Events\Notifications\ChannelsInstrumentStopped;
use App\Events\Notifications\ChannelsIntegrationSettingsChanged;
use App\Events\Notifications\ClientsDirectoryChanged;
use App\Events\Notifications\FundsReceived;
use App\Events\Notifications\IntegrationSyncFailed;
use App\Events\Notifications\PlanningApprovalRequired;
use App\Events\Notifications\PlanningMissing;
use App\Events\Notifications\ProjectBudgetLow;
use App\Listeners\Notifications\CreateChannelManagerChangedNotification;
use App\Listeners\Notifications\CreateChannelsAnalyticsStoppedNotification;
use App\Listeners\Notifications\CreateChannelsBonusCalculatedNotification;
use App\Listeners\Notifications\CreateChannelsInstrumentStoppedNotification;
use App\Listeners\Notifications\CreateChannelsIntegrationSettingsChangedNotification;
use App\Listeners\Notifications\CreateClientsDirectoryChangedNotification;
use App\Listeners\Notifications\CreateFundsReceivedNotification;
use App\Listeners\Notifications\CreateIntegrationSyncFailedNotification;
use App\Listeners\Notifications\CreatePlanningApprovalRequiredNotification;
use App\Listeners\Notifications\CreatePlanningMissingNotification;
use App\Listeners\Notifications\CreateProjectBudgetLowNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ProjectBudgetLow::class => [
            CreateProjectBudgetLowNotification::class,
        ],
        FundsReceived::class => [
            CreateFundsReceivedNotification::class,
        ],
        ChannelManagerChanged::class => [
            CreateChannelManagerChangedNotification::class,
        ],
        ChannelsAnalyticsStopped::class => [
            CreateChannelsAnalyticsStoppedNotification::class,
        ],
        ChannelsInstrumentStopped::class => [
            CreateChannelsInstrumentStoppedNotification::class,
        ],
        ChannelsIntegrationSettingsChanged::class => [
            CreateChannelsIntegrationSettingsChangedNotification::class,
        ],
        ChannelsBonusCalculated::class => [
            CreateChannelsBonusCalculatedNotification::class,
        ],
        PlanningMissing::class => [
            CreatePlanningMissingNotification::class,
        ],
        PlanningApprovalRequired::class => [
            CreatePlanningApprovalRequiredNotification::class,
        ],
        ClientsDirectoryChanged::class => [
            CreateClientsDirectoryChangedNotification::class,
        ],
        IntegrationSyncFailed::class => [
            CreateIntegrationSyncFailedNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // сюда ничего не нужно — достаточно $listen
    }

    /**
     * Disable event discovery (we use explicit $listen).
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
