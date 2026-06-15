<?php

namespace App\Observers;

use App\Models\LandingLead;
use App\Services\LandingLeadNotificationService;

class LandingLeadObserver
{
    public function __construct(
        private readonly LandingLeadNotificationService $notifications,
    ) {}

    public function created(LandingLead $lead): void
    {
        $this->notifications->send($lead);
    }
}
