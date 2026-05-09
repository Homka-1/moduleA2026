<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegistrationResource;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function createRegistration(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        $participant = Participant::query()->where('user_id', auth()->id())->first();
        $event = Event::query()->where('id', $request->event_id)->first();

        if (!$participant) {
            return response()->json(['message' => 'Create a participant profile'], 422);
        }

        $registration = Registration::query()
            ->where('participant_id', $participant->id)
            ->where('event_id', $event->id)
            ->exists();

        if ($registration) {
            return response()->json(['message' => 'Conflict'], 409);
        }

        $registered = Registration::query()->create([
            'event_id' => $request->event_id,
            'participant_id' => $participant->id,
            'status' => 'PENDING'
        ]);

        return response()->json([
            'message' => 'create success',
            'data' => [
                'id' => $registered->id,
                'status' => $registered->status
            ],
        ], 201);
    }

    public function confirmRegistration(Registration $registration)
    {
        if(auth()->user()->role !== 'ADMIN'){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $confirmedRegistrations = Registration::query()->where('status', 'CONFIRMED')->count();
        $eventCapacity = Event::query()->where('id', $registration->event_id)->pluck('capacity')->first();

        if($registration->status !== 'PENDING' || $eventCapacity <= $confirmedRegistrations){
            return response()->json(['message' => 'Conflict'], 409);
        }

        $registration->update([
            'status' => 'CONFIRMED'
        ]);
        return response()->json([
            'message' => 'confirm success',
            'data' => [
               'id' => $registration->id,
               'status' => 'CONFIRMED'
            ],
        ], 200);
    }

    public function cancelRegistration(Registration $registration)
    {
        $participantId = Participant::query()->where('user_id', auth()->id())->pluck('id')->first();

        if($participantId !== $registration->participant_id && auth()->user()->role !== 'ADMIN'){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if($registration->status == 'CANCELLED'){
            return response()->json(['message' => 'Conflict'], 409);
        }

        $registration->update([
            'status' => 'CANCELLED'
        ]);

        return response()->json([
            'message' => 'cancel success',
            'data' => [
               'id' => $registration->id,
               'status' => 'CANCELLED',
            ],
        ], 200);
    }

    public function myRegistrations()
    {
        $participantId = Participant::query()->where('user_id', auth()->id())->pluck('id')->first();
        $registrations = Registration::query()->where('participant_id', $participantId)->get();
        return response()->json([
            'data' => RegistrationResource::collection($registrations),
        ], 200);
    }
}
