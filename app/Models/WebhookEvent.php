<?php

namespace App\Models;

use App\Enums\WebhookProcessingStatus;
use Database\Factories\WebhookEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $event_id
 * @property string|null $provider_generation_id
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property WebhookProcessingStatus $status
 * @property int $delivery_attempt
 * @property string|null $processing_error
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Generation|null $generation
 */
class WebhookEvent extends Model
{
    /** @use HasFactory<WebhookEventFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'provider_generation_id',
        'event_type',
        'payload',
        'status',
        'delivery_attempt',
        'processing_error',
        'received_at',
        'processed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WebhookProcessingStatus::class,
            'payload' => 'array',
            'delivery_attempt' => 'integer',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * The associated generation, if one exists with the matching provider ID.
     *
     * @return BelongsTo<Generation, $this>
     */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class, 'provider_generation_id', 'provider_generation_id');
    }

    /**
     * Scope a query to only include pending webhook events.
     *
     * @param  Builder<$this>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', WebhookProcessingStatus::Pending);
    }

    /**
     * Scope a query to only include processed webhook events.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeProcessed(Builder $query): void
    {
        $query->where('status', WebhookProcessingStatus::Processed);
    }

    /**
     * Scope a query to only include failed webhook events.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeFailed(Builder $query): void
    {
        $query->where('status', WebhookProcessingStatus::Failed);
    }
}
