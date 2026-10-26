<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class AppointmentScheduled
 *
 * Domain event dispatched whenever a patient or doctor successfully books
 * and confirms a clinical appointment slot. Triggers asynchronous notification
 * jobs and audit logging pipelines.
 */
final class AppointmentScheduled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Appointment  $appointment  The newly scheduled clinical appointment.
     */
    public function __construct(
        public readonly Appointment $appointment,
    ) {}
}
