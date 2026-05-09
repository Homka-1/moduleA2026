<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function listEvents(Request $request)
    {
        $query = Event::query();

        if ($request->search){
            $query->where('title', 'LIKE', "%{$request->search}%")->orWhere('location', 'LIKE', "%{$request->search}%");
        }

        if($request->date){
            $query->whereDate('event_date', $request->date);
        }

        $events = $query->paginate(10);

        return response()->json([
            'data' => EventResource::collection($events),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    public function createEvent(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'event_date' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:' . now()->addYear()->format('Y-m-d'),
            ],
            'capacity' => 'required|integer|min:1',
        ]);

        if(auth()->user()->role !== 'ADMIN'){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $event = Event::query()->create([
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'event_date' => $request->event_date,
            'capacity' => $request->capacity,
        ]);

        return response()->json([
            'message' => 'create success',
            'data' => [
                'id' => $event->id,
                'title' => $event->title,
                'event_date' => $event->event_date,
            ],
        ], 201);
    }

    public function getEvent(Event $event)
    {
        $participants = Registration::query()
            ->where('event_id', $event->id)
            ->with('participant')
            ->get()
            ->map(fn($r) => [
                'id'    => $r->participant->id,
                'name'  => $r->participant->name,
                'phone' => $r->participant->phone,
            ]);

        $resource = new EventResource($event);

        return response()->json([
            'data' => array_merge($resource->resolve(request()), [
                'participants' => $participants,
            ]),
        ]);
    }

    public function updateEvent(Request $request, Event $event)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'event_date' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:' . now()->addYear()->format('Y-m-d'),
            ],
            'capacity' => 'required|integer|min:1',
        ]);

        if(auth()->user()->role !== 'ADMIN'){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $confirmedRegistrations = Registration::query()->where('status', 'CONFIRMED')->count();

        if ($request->capacity >= $confirmedRegistrations){
            $event->update([
                'title' => $request->title,
                'description' => $request->description,
                'location' => $request->location,
                'event_date' => $request->event_date,
                'capacity' => $request->capacity,
            ]);
        } else {
            return response()->json(['message' => 'The capacity must be greater than number of confirmed registrations'], 409);
        }

        return response()->json([
            'message' => 'update success',
            'data' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'event_date' => $event->event_date,
                'capacity' => $event->capacity,
            ]
        ], 200);
    }

    public function deleteEvent(Event $event)
    {
        if(auth()->user()->role !== 'ADMIN'){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $related = Registration::query()->where('event_id', $event->id);

        $related->delete();
        $event->delete();

        return response()->json(['message' => 'delete success'], 200);
    }

    public function myStatus(Event $event)
    {
        $participant = Participant::query()->where('user_id', auth()->id())->first();
        $registration = Registration::query()->where('participant_id', $participant->id)->where('event_id', $event->id)->first();

        if (!$registration) {
            return response()->json(['data' => null], 200);
        }

        return response()->json([
            'data' => [
                'event_id' => $event->id,
                'status' => $registration->status,
            ],
        ], 200);
    }
}
