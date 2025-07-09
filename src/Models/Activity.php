<?php

namespace Martin3r\LaravelActivityLog\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'user_id',
        'properties',
        'activity_type',
        'description',
        'message',
        'metadata',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'properties' => 'array',
        'metadata'   => 'array',
    ];

    /**
     * Polymorphic inverse relation to the subject model.
     */
    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Relation to the user who performed the activity.
     */
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}