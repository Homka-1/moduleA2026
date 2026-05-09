<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = ['name', 'phone', 'user_id'];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
