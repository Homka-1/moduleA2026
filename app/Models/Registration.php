<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    protected $fillable = ['event_id', 'participant_id', 'status'];

    public function event(): BelongsTo
    {
        return $this->belongsTo('App\Models\Event');
    }
    public function participant(): BelongsTo
    {
        return $this->belongsTo('App\Models\Participant');
    }
}
