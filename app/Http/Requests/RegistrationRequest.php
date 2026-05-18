<?php

namespace App\Http\Requests;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => [
                'required', 'exists:events,id',
                function($attribute, $value, $fail){
                    $event = Event::find($value);

                    if ($event && Carbon::parse($event->event_date)->isPast()){
                        $fail('Event has already started');
                    }
                }
            ],
        ];
    }
}
