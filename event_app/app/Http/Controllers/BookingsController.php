<?php

namespace App\Http\Controllers;

use App\Events\BookingStatusUpdateNotification;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingsController extends Controller
{
    public function getAllbookings()
    {
        $bookings = Booking::with(['user', 'event'])->get();
        return response()->json(['message'=> 'Data found', 'data'=> $bookings]);   
    }
    
    public function getBooking(Request $request)
    {
        $booking = Booking::with(['user', 'event'])->where('id', $request->id)->first();
        return response()->json(['message'=> 'Data found', 'data'=> $booking]);   
    }
    
    
    public function getMemberbookings(Request $request)
    {
        $bookings = Booking::with(['user', 'event'])->where('user_id', $request->id)->get();
        return response()->json(['message'=> 'Data found', 'data'=> $bookings]);   
    }
    
    
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'user_id' => 'required',
            'event_id' => 'required',
            'ticket_qty' => 'required',
            'ticket_price' => 'required',
            'total_price' => 'required',
        ]);

        if($validation->fails()){
            return response()->json([
                'status'=> false,
                'message'=> 'validation error',
                'errors'=> $validation->errors()
            ], 400);
        }

        // Check if event exists and is still available
        $event = Event::find($request->event_id);
        if (!$event) {
            return response()->json([
                'status'=> false,
                'message'=> 'Event not found'
            ], 404);
        }

        // Check if event has already ended
        if (Carbon::parse($event->end_time)->isPast()) {
            return response()->json([
                'status'=> false,
                'message'=> 'Cannot book expired event'
            ], 400);
        }

        // Check if user has already booked this event (and booking is still active)
        $existingBooking = Booking::where('user_id', $request->user_id)
            ->where('event_id', $request->event_id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($existingBooking) {
            // Check if the existing booking's event has ended
            if (Carbon::parse($event->end_time)->isFuture()) {
                return response()->json([
                    'status'=> false,
                    'message'=> 'You have already booked this event'
                ], 400);
            }
        }

        $booking = Booking::create([
            'user_id' => $request->user_id,
            'event_id' => $request->event_id,
            'ticket_qty' => $request->ticket_qty,
            'ticket_price' => $request->ticket_price,
            'total_price' => $request->total_price,
            'status' => 'pending',
        ]);  

        return response()->json([
            'status'=> true,
            'message'=> 'Booking was successful. Waiting for admin approval',
            'data'=> $booking
        ], 200);
    }

    public function updateBooking(Request $request) {
        $validation = Validator::make($request->all(), [
            'status' => 'required',
        ]);

        if($validation->fails()){
            return response()->json([
                'status'=> false,
                'message'=> 'validation error',
                'errors'=> $validation->errors()
            ], 400);
        }

        $booking = Booking::findOrFail($request->id);

        $booking->update($request->all());

        $bookingData = Booking::with(['user', 'event'])->where('id', $booking->id)->first();

        event(new BookingStatusUpdateNotification($bookingData));

        return response()->json(['message' => 'update success', 'data' => $booking]);
    }

    /**
     * Check if an event is available for booking by a specific user
     */
    public function checkEventAvailability(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'user_id' => 'required',
            'event_id' => 'required',
        ]);

        if($validation->fails()){
            return response()->json([
                'status'=> false,
                'message'=> 'validation error',
                'errors'=> $validation->errors()
            ], 400);
        }

        $event = Event::find($request->event_id);
        if (!$event) {
            return response()->json([
                'status'=> false,
                'message'=> 'Event not found',
                'available' => false
            ], 404);
        }

        // Check if event has ended
        $isExpired = Carbon::parse($event->end_time)->isPast();
        
        // Check if user has active booking for this event
        $hasActiveBooking = Booking::where('user_id', $request->user_id)
            ->where('event_id', $request->event_id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        $available = !$isExpired && !$hasActiveBooking;

        return response()->json([
            'status'=> true,
            'available' => $available,
            'is_expired' => $isExpired,
            'has_active_booking' => $hasActiveBooking,
            'event_end_time' => $event->end_time
        ]);
    }

    /**
     * Auto-expire bookings for events that have ended
     */
    public function autoExpireBookings()
    {
        $now = Carbon::now();
        
        // Find all confirmed/pending bookings for events that have ended
        $expiredBookings = Booking::whereIn('status', ['pending', 'confirmed'])
            ->whereHas('event', function ($query) use ($now) {
                $query->where('end_time', '<', $now);
            })
            ->with('event')
            ->get();

        $expiredCount = 0;
        foreach ($expiredBookings as $booking) {
            $booking->update(['status' => 'completed']);
            $expiredCount++;
        }

        return response()->json([
            'status' => true,
            'message' => "Auto-expired {$expiredCount} bookings",
            'expired_count' => $expiredCount
        ]);
    }
}