<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AppointmentScheduled;
use App\Jobs\SendAppointmentNotificationJob;

/**
 * Class DispatchAppointmentNotificationListener
 *
 * Listens for AppointmentScheduled domain events and queues background
 * notification delivery jobs on the Redis asynchronous worker queue.
 */
class DispatchAppointmentNotificationListener
{
    /**
     * Handle the event.
     *
     * @param  AppointmentScheduled  $event  The appointment scheduled event.
     */
    public function handle(AppointmentScheduled $event): void
    {
        SendAppointmentNotificationJob::dispatch($event->appointment->id);
    }
}
