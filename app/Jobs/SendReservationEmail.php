<?php

namespace App\Jobs;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReservationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $reservation;

    /**
     * Create a new job instance.
     */
    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load necessary relationships
        $this->reservation->load(['user', 'timeSlot.consultant']);
        
        $user = $this->reservation->user;
        $timeSlot = $this->reservation->timeSlot;
        $consultant = $timeSlot->consultant;

        // Simulate sending email (log instead of actual email)
        Log::info('Reservation Confirmation Email Sent', [
            'reservation_id' => $this->reservation->id,
            'client_name' => $user->name,
            'client_email' => $user->email,
            'consultant_name' => $consultant->name,
            'appointment_date' => $timeSlot->start_time->format('Y-m-d'),
            'appointment_time' => $timeSlot->start_time->format('H:i') . ' - ' . $timeSlot->end_time->format('H:i'),
            'message' => "Dear {$user->name}, your consultation with {$consultant->name} has been confirmed for {$timeSlot->start_time->format('Y-m-d H:i')} - {$timeSlot->end_time->format('H:i')}."
        ]);

        // Here you would normally send an actual email
        // Mail::to($user->email)->send(new ReservationConfirmationMail($this->reservation));
        
        // Simulate processing time
        sleep(1);
        
        Log::info('Email sent successfully to: ' . $user->email);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send reservation confirmation email', [
            'reservation_id' => $this->reservation->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
