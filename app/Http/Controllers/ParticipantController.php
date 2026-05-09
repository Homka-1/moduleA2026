<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Registration;
use http\Env\Response;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function myParticipant()
    {
        $participant = Participant::query()->where('user_id', auth()->id())->first();

        if (!$participant) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'phone' => $participant->phone,
            ],
        ], 200);
    }

    public function createParticipant(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        if (auth()->id() == Participant::query()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'Conflict'], 409);
        }

        $participant = Participant::query()->create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'phone' => $request->phone
        ]);

        return response()->json([
            'message' => 'create success',
            'data' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'phone' => $participant->phone
            ],
        ], 201);
    }

    public function updateParticipant(Request $request, Participant $participant)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        if (auth()->id() == $participant->user_id) {
            $participant->update([
                'name' => $request->name,
                'phone' => $request->phone,
            ]);

            return response()->json([
                'message' => 'update success',
                'data' => [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'phone' => $participant->phone,
                ],
            ], 201);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
    public function deleteParticipant(Participant $participant)
    {

        if(auth()->id() !== $participant->user_id){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $hasActiveRegistration = Registration::query()
            ->where('participant_id', $participant->id)
            ->whereIn('status', ['PENDING', 'CONFIRMED'])
            ->exists();

        if ($hasActiveRegistration) {
            return response()->json(['message' => 'Conflict'], 409);
        }

        $participant->delete();
        return response()->json(['message' => 'delete success'], 200);

    }
}
