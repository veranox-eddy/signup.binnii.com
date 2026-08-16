<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One push-worker cycle, for observability; purged after 30 days. */
class PushRun extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
