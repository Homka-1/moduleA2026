<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationRequest;
use App\Http\Resources\RegistrationResource;
use App\Http\Resources\StatusResource;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function createRegistration(RegistrationRequest $request)
    {
        $request->validated();

        $participant = Participant::query()->where('user_id', auth()->id())->first();

        if (!$participant){
            return response()->json(['message' => 'Create a participant profile'], 422);
        }

        $conflict = Registration::query()
            ->where('event_id', $request->event_id)
            ->where('participant_id', $participant->id)
            ->whereNot('status', 'cancelled')
            ->exists();

        if ($conflict){
            return response()->json(['message' => 'Conflict'], 409);
        }

        $registration = Registration::query()->create([
            'event_id' => $request->event_id,
            'participant_id' => $participant->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'create success',
            'data' => new RegistrationResource($registration),
        ], 201);
    }

    public function confirmRegistration(Registration $registration)
    {
        if ($registration->status !== 'pending'){
            return response()->json(['message' => 'Conflict'], 409);
        }
        if (auth()->user()->role !== 'admin'){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $registration->update(['status' => 'confirmed']);
        return response()->json([
            'message' => 'update success',
            'data' => new RegistrationResource($registration),
        ], 200);
    }

    public function cancelRegistration(Registration $registration)
    {
        $participant = Participant::query()->where('user_id', auth()->id())->first();

        if ($registration->status === 'cancelled'){
            return response()->json(['message' => 'Conflict'], 409);
        }
        if (auth()->user()->role !== 'admin' && $registration->participant->id !== $participant->id){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $registration->update(['status' => 'cancelled']);
        return response()->json([
            'message' => 'cancel success',
            'data' => new RegistrationResource($registration),
        ], 200);
    }

    public function myRegistrations(){
        $participant = Participant::query()->where('user_id', auth()->id())->first();
        $registrations = Registration::query()->where('participant_id', $participant->id)->get();
        return response()->json(['data' => StatusResource::collection($registrations)], 200);
    }
}
