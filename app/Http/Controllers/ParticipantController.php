<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateParticipantRequest;
use App\Http\Resources\ParticipantResource;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function myParticipant()
    {
        $participant = Participant::query()->where('user_id', auth()->id())->first();

        if (!$participant) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(['data' => new ParticipantResource($participant)], 200);
    }

    public function createParticipant(CreateParticipantRequest $request)
    {
        $request->validated();

        $exists = Participant::query()->where('user_id', auth()->id())->first();

        if ($exists) {
            return response()->json(['message' => 'Conflict'], 409);
        }

        $participant = Participant::query()->create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'message' => 'create success',
            'data' => new ParticipantResource($participant)
        ], 201);
    }

    public function updateParticipant(CreateParticipantRequest $request, Participant $participant)
    {
        $request->validated();

        $exists = Participant::query()->where('user_id', auth()->id())->first();

        if (!$exists || $exists->id !== $participant->id)
        {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $participant->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'message' => 'update success',
            'data' => new ParticipantResource($participant)
        ], 200);

    }

    public function deleteParticipant(Participant $participant)
    {
        $exists = Participant::query()->where('user_id', auth()->id())->first();

        $registrations = Registration::query()->where('participant_id', $participant->id);

        if (!$exists || $exists->id !== $participant->id)
        {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $conflict = Registration::query()
            ->where('participant_id', $participant->id)
            ->whereNot('status', 'cancelled')
            ->exists();

        if ($conflict){
            return response()->json(['message' => 'Conflict'], 409);
        }

        $registrations->delete();
        $participant->delete();
        return response()->json(['message' => 'delete success'], 200);
    }
}
