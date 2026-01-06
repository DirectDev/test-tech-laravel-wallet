<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'start_date',
        'stop_date',
        'frequency',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'stop_date' => 'date',
            'frequency' => 'integer',
            'amount' => 'float',
        ];
    }

    /**
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

