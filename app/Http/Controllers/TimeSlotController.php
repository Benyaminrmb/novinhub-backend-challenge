<?php

namespace App\Http\Controllers;

use App\Models\TimeSlot;
use App\Http\Requests\StoreTimeSlotRequest;
use App\Http\Requests\UpdateTimeSlotRequest;
use App\Http\Resources\AvailableTimeSlotResource;
use App\Http\Resources\TimeSlotResource;
use App\Http\Resources\TimeSlotCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TimeSlotController extends Controller
{
    /**
     * Display available time slots
     */
    public function available()
    {
        // Try to get from cache first
        $cacheKey = 'available_time_slots';
        $timeSlots = Cache::remember($cacheKey, 300, function () { // Cache for 5 minutes
            return TimeSlot::with('consultant:id,name')
                ->available()
                ->future()
                ->orderBy('start_time')
                ->get();
        });

        return AvailableTimeSlotResource::collection($timeSlots);
    }

    /**
     * Store a newly created time slot (consultant only)
     */
    public function store(StoreTimeSlotRequest $request): JsonResponse
    {
        // Check authorization
        if (!$request->user()->can('create', TimeSlot::class)) {
            return response()->json([
                'message' => 'Only consultants can create time slots',
            ], 403);
        }

        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);

        // Check for overlapping time slots
        if (TimeSlot::hasOverlappingSlots($request->user()->id, $startTime, $endTime)) {
            return response()->json([
                'message' => 'Time slot overlaps with existing time slots',
                'errors' => [
                    'time_conflict' => ['This time slot conflicts with your existing time slots']
                ]
            ], 422);
        }

        $timeSlot = TimeSlot::create([
            'consultant_id' => $request->user()->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        // Clear cache when new time slot is created
        Cache::forget('available_time_slots');

        return response()->json([
            'message' => 'Time slot created successfully',
            'data' => new TimeSlotResource($timeSlot->load('consultant')),
        ], 201);
    }

    /**
     * Display the specified time slot
     */
    public function show($timeSlotId): JsonResponse
    {
        $timeSlot = TimeSlot::with(['consultant', 'reservation'])->find($timeSlotId);
        
        if (!$timeSlot) {
            return response()->json([
                'message' => 'Time slot not found',
            ], 404);
        }
   
        return response()->json([
            'data' => new TimeSlotResource($timeSlot),
        ]);
    }

    /**
     * Update the specified time slot (consultant only)
     */
    public function update(UpdateTimeSlotRequest $request, $timeSlotId): JsonResponse
    {
        // Manually load the time slot to avoid route model binding issues
        $timeSlot = TimeSlot::find($timeSlotId);
        
        if (!$timeSlot) {
            return response()->json([
                'message' => 'Time slot not found',
            ], 404);
        }
        
        // Check authorization
        if (!$request->user()->can('update', $timeSlot)) {
            return response()->json([
                'message' => 'You can only update your own time slots',
            ], 403);
        }

        // Check if time slot is already reserved
        if (!$timeSlot->isAvailable()) {
            return response()->json([
                'message' => 'Cannot update a reserved time slot',
            ], 422);
        }

        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);

        // Check for overlapping time slots (exclude current slot)
        if (TimeSlot::hasOverlappingSlots($request->user()->id, $startTime, $endTime, $timeSlot->id)) {
            return response()->json([
                'message' => 'Time slot overlaps with existing time slots',
                'errors' => [
                    'time_conflict' => ['This time slot conflicts with your existing time slots']
                ]
            ], 422);
        }

        $timeSlot->update([
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        // Clear cache when time slot is updated
        Cache::forget('available_time_slots');

        return response()->json([
            'message' => 'Time slot updated successfully',
            'data' => new TimeSlotResource($timeSlot->load('consultant')),
        ]);
    }

    /**
     * Remove the specified time slot (consultant only)
     */
    public function destroy(Request $request, $timeSlotId): JsonResponse
    {
        // Manually load the time slot to avoid route model binding issues
        $timeSlot = TimeSlot::find($timeSlotId);
        
        if (!$timeSlot) {
            return response()->json([
                'message' => 'Time slot not found',
            ], 404);
        }

        // Check authorization using Policy
        if (!$request->user()->can('delete', $timeSlot)) {
            return response()->json([
                'message' => 'You can only delete your own time slots',
            ], 403);
        }

        // Check if time slot is already reserved
        if (!$timeSlot->isAvailable()) {
            return response()->json([
                'message' => 'Cannot delete a reserved time slot',
            ], 422);
        }

        $timeSlot->delete();

        // Clear cache when time slot is deleted
        Cache::forget('available_time_slots');

        return response()->json([
            'message' => 'Time slot deleted successfully',
        ]);
    }

    /**
     * Get consultant's own time slots
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('viewAny', TimeSlot::class) || !$request->user()->isConsultant()) {
            return response()->json([
                'message' => 'Only consultants can view their time slots',
            ], 403);
        }

        $timeSlots = TimeSlot::forConsultant($request->user()->id)
            ->with(['consultant', 'reservation.user'])
            ->orderBy('start_time')
            ->get();

        return new TimeSlotCollection($timeSlots);
    }
}
