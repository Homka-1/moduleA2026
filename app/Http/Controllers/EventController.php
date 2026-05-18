<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function listEvents(Request $request)
    {
        $query = Event::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title' , 'LIKE', "%{$request->search}%")
                    ->orWhere('location' , 'LIKE', "%{$request->search}%");
            });
        }

        if ($request->filled('date')) {
            try{
                $date = Carbon::parse($request->query('date'))->format('Y-m-d');

                $query->whereDate('event_date', $date);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Invalid date'], 400);
            }
        }

        $result = $query->paginate(10);

        return response()->json([
            'data' => EventResource::collection($result),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ]
        ]);
    }

    public function createEvent(CreateEventRequest $request)
    {
        $request->validated();

        if (auth()->user()->role !== 'admin')
        {
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
            ]
        ], 201);
    }

    public function getEvent(Event $event){

        $participants = Registration::query()
            ->where('event_id', $event->id)
            ->with('participant')
            ->get()
            ->map(fn($r) => [
                'id' => $r->participant->id,
                'name' => $r->participant->name,
                'phone' => $r->participant->phone,
        ]);

        return response()->json([
            'data' => new EventResource($event),
            'participants' => $participants,
        ]);
    }

    public function updateEvent(CreateEventRequest $request, Event $event)
    {
        $request->validated();

        $reg_count = Registration::query()->where('event_id', $event->id)->where('status', 'confirmed')->count();

        if (auth()->user()->role !== 'admin'){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if($request->capacity < $reg_count){
            return response()->json(['message' => 'Conflict'],409);
        }

        $event->update([
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'event_date' => $request->event_date,
            'capacity' => $request->capacity,
        ]);

        return response()->json([
            'message' => 'update success',
            'data' => new EventResource($event),
        ], 200);
    }

    public function deleteEvent(Event $event){

        $related = Registration::query()->where('event_id', $event->id);

        if (auth()->user()->role !== 'admin'){
            return response()->json(['message' => 'Forbidden'], 403);
        }

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
