<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;

use App\Events\Notifications\ProjectBudgetLow;
use App\Listeners\Notifications\CreateProjectBudgetLowNotification;

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
