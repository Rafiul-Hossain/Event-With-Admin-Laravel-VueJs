<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class EventsController extends Controller
{
    public function events(){
        $events = Event::all();
        return response()->json([
            'status'=> true,
            'message'=> 'Events data',
            'data'=> $events,
        ], 200);
    }

    public function getEvent(Event $event) {
        return response()->json([
            'status'=> true,
            'message'=> 'Event data',
            'data'=> $event,
        ], 200);
    }

    public function createEvent(Request $request) {
        $validation = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'ticket_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'event_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg'
        ]);

        if($validation->fails()){
            return response()->json([
                'status'=> false,
                'message'=> 'Validation error',
                'errors'=> $validation->errors()
            ], 400);
        }

        // Create new event instance
        $event = new Event();
        $event->title = $request->title;
        $event->start_time = $request->start_time;
        $event->end_time = $request->end_time;
        $event->ticket_price = $request->ticket_price;
        $event->description = $request->description;
        $event->event_image = $request->event_image;
       
$event_image = null;
        if($request->hasFile('event_image')){
            $image = $request->file('event_image');
            $image_name = 'event_image_'.time().'.'.$image->getClientOriginalExtension();
            $image->storeAs('event_image', $image_name);
            $profile_image = 'storage/event_image/'.$image_name;
    
    // Store the image
    $path = $image->storeAs('public/event_image', $image_name);
    
    // Save only the URL path
    $event->event_image = url('storage/event_image/'.$image_name);
}

        $event->save();

        return response()->json([
            'status'=> true,
            'message'=> 'Event created successfully',
            'data'=> $event
        ], 201);
    }

    public function updateEvent(Request $request) {
        $validation = Validator::make($request->all(), [
            'id' => 'required|exists:events,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'ticket_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'event_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if($validation->fails()){
            return response()->json([
                'status'=> false,
                'message'=> 'Validation error',
                'errors'=> $validation->errors()
            ], 400);
        }
       
        $event = Event::findOrFail($request->id);
        
        // Update all fields
        $event->title = $request->title;
        $event->start_time = $request->start_time;
        $event->end_time = $request->end_time;
        $event->ticket_price = $request->ticket_price;
        $event->description = $request->description;

        // Handle event image upload if present
        if($request->hasFile('event_image')){
    // Delete old image
    if($event->event_image){
        $oldImageName = basename($event->event_image);
        $oldImagePath = 'public/event_image/' . $oldImageName;
        if(Storage::exists($oldImagePath)) {
            Storage::delete($oldImagePath);
        }
    }
    
    $image = $request->file('event_image');
    $image_name = 'event_image_'.time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
    $image->storeAs('public/event_image', $image_name);
    $event->event_image = asset('storage/event_image/'.$image_name);
}

        $event->save();

        return response()->json([
            'status'=> true,
            'message'=> 'Event updated successfully',
            'data'=> $event
        ], 200);
    }

    public function deleteEvent($id) {
        try {
            $event = Event::findOrFail($id);
            
            $event->delete();

            return response()->json([
                'status'=> true,
                'message'=> 'Event deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'=> false,
                'message'=> 'Failed to delete event'
            ], 500);
        }
    }
}