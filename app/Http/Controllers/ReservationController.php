<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\TimeSlot;
use App\Events\ReservationCreated;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Http\Resources\ReservationCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    /**
     * Display user's reservations
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('viewAny', Reservation::class) || !$request->user()->isClient()) {
            return response()->json([
                'message' => 'Only clients can view reservations',
            ], 403);
        }

        $reservations = Reservation::forUser($request->user()->id)
            ->with(['timeSlot.consultant', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return new ReservationCollection($reservations);
    }

    /**
     * Store a newly created reservation (client only)
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        // Check authorization explicitly to provide custom error message
        if (!$request->user()->can('create', Reservation::class)) {
            return response()->json([
                'message' => 'Only clients can make reservations',
            ], 403);
        }

        $timeSlot = TimeSlot::findOrFail($request->time_slot_id);

        // Check if time slot is in the future
        if (!$timeSlot->isFuture()) {
            return response()->json([
                'message' => 'Cannot reserve past time slots',
                'errors' => [
                    'time_slot_id' => ['This time slot is in the past']
                ]
            ], 422);
        }

        // Check if user already has a reservation for this time slot FIRST
        $existingReservation = Reservation::where('user_id', $request->user()->id)
            ->where('time_slot_id', $request->time_slot_id)
            ->first();

        if ($existingReservation) {
            return response()->json([
                'message' => 'You have already reserved this time slot',
                'errors' => [
                    'time_slot_id' => ['You already have a reservation for this time slot']
                ]
            ], 422);
        }

        // Check if time slot is available
        if (!$timeSlot->isAvailable()) {
            return response()->json([
                'message' => 'Time slot is already reserved',
                'errors' => [
                    'time_slot_id' => ['This time slot is no longer available']
                ]
            ], 422);
        }

        try {
            DB::beginTransaction();

            $reservation = Reservation::create([
                'user_id' => $request->user()->id,
                'time_slot_id' => $request->time_slot_id,
            ]);

            // Dispatch event for email notification
            event(new ReservationCreated($reservation));

            DB::commit();

            // Clear cache when new reservation is created
            Cache::forget('available_time_slots');

            return response()->json([
                'message' => 'Reservation created successfully',
                'data' => new ReservationResource($reservation->load(['timeSlot.consultant', 'user'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'message' => 'Failed to create reservation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified reservation
     */
    public function show(Request $request, Reservation $reservation): JsonResponse
    {
        // Check authorization using Policy
        if (!$request->user()->can('view', $reservation)) {
            return response()->json([
                'message' => 'You can only view your own reservations or reservations for your time slots',
            ], 403);
        }

        return response()->json([
            'data' => new ReservationResource($reservation->load(['timeSlot.consultant', 'user'])),
        ]);
    }

    /**
     * Cancel a reservation (client only)
     */
    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        // Check authorization using Policy
        if (!$request->user()->can('delete', $reservation)) {
            return response()->json([
                'message' => 'You can only cancel your own reservations',
            ], 403);
        }

        // Check if reservation is for a future time slot
        if (!$reservation->timeSlot->isFuture()) {
            return response()->json([
                'message' => 'Cannot cancel past reservations',
            ], 422);
        }

        $reservation->delete();

        // Clear cache when reservation is cancelled
        Cache::forget('available_time_slots');

        return response()->json([
            'message' => 'Reservation cancelled successfully',
        ]);
    }

    /**
     * Get future reservations for a user
     */
    public function future(Request $request)
    {
        if (!$request->user()->can('viewAny', Reservation::class) || !$request->user()->isClient()) {
            return response()->json([
                'message' => 'Only clients can view future reservations',
            ], 403);
        }

        $reservations = Reservation::forUser($request->user()->id)
            ->future()
            ->with(['timeSlot.consultant', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return new ReservationCollection($reservations);
    }

    /**
     * Get reservations for consultant's time slots
     */
    public function consultantReservations(Request $request)
    {
        if (!$request->user()->can('viewAny', Reservation::class) || !$request->user()->isConsultant()) {
            return response()->json([
                'message' => 'Only consultants can view their reservations',
            ], 403);
        }

        $reservations = Reservation::whereHas('timeSlot', function ($query) use ($request) {
                $query->where('consultant_id', $request->user()->id);
            })
            ->with(['timeSlot.consultant', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return new ReservationCollection($reservations);
    }
}
