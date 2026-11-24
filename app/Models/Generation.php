<?php

namespace App\Models;

use App\Enums\GenerationStatus;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $provider_generation_id
 * @property string $idempotency_key
 * @property string $prompt
 * @property ImageSize $size
 * @property ImageQuality $quality
 * @property array<string>|null $reference_images
 * @property array<string, string>|null $metadata
 * @property GenerationStatus $status
 * @property string|null $provider_status
 * @property string|null $image_url
 * @property Carbon|null $image_url_expires_at
 * @property string|null $error_code
 * @property string|null $error_message
 * @property Carbon|null $submitted_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property int|null $estimated_input_text_tokens
 * @property int|null $estimated_input_image_tokens
 * @property int|null $estimated_output_tokens
 * @property int|null $estimated_reasoning_tokens_min
 * @property int|null $estimated_reasoning_tokens_max
 * @property int|null $estimated_total_tokens_min
 * @property int|null $estimated_total_tokens_max
 * @property int|null $actual_input_text_tokens
 * @property int|null $actual_input_image_tokens
 * @property int|null $actual_output_tokens
 * @property int|null $actual_reasoning_tokens
 * @property int|null $actual_total_tokens
 * @property string|null $estimated_provider_cost
 * @property string|null $estimated_user_cost
 * @property string|null $actual_provider_cost
 * @property string|null $actual_user_cost
 * @property string $applied_markup_rate
 * @property array<string, string>|null $pricing_rate_snapshot
 * @property Carbon|null $settled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $user
 */
class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'provider_generation_id',
        'idempotency_key',
        'prompt',
        'size',
        'quality',
        'reference_images',
        'metadata',
        'status',
        'provider_status',
        'image_url',
        'image_url_expires_at',
        'error_code',
        'error_message',
        'submitted_at',
        'started_at',
        'completed_at',
        'estimated_input_text_tokens',
        'estimated_input_image_tokens',
        'estimated_output_tokens',
        'estimated_reasoning_tokens_min',
        'estimated_reasoning_tokens_max',
        'estimated_total_tokens_min',
        'estimated_total_tokens_max',
        'actual_input_text_tokens',
        'actual_input_image_tokens',
        'actual_output_tokens',
        'actual_reasoning_tokens',
        'actual_total_tokens',
        'estimated_provider_cost',
        'estimated_user_cost',
        'actual_provider_cost',
        'actual_user_cost',
        'applied_markup_rate',
        'pricing_rate_snapshot',
        'settled_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GenerationStatus::class,
            'size' => ImageSize::class,
            'quality' => ImageQuality::class,
            'reference_images' => 'array',
            'metadata' => 'array',
            'pricing_rate_snapshot' => 'array',
            'estimated_input_text_tokens' => 'integer',
            'estimated_input_image_tokens' => 'integer',
            'estimated_output_tokens' => 'integer',
            'estimated_reasoning_tokens_min' => 'integer',
            'estimated_reasoning_tokens_max' => 'integer',
            'estimated_total_tokens_min' => 'integer',
            'estimated_total_tokens_max' => 'integer',
            'actual_input_text_tokens' => 'integer',
            'actual_input_image_tokens' => 'integer',
            'actual_output_tokens' => 'integer',
            'actual_reasoning_tokens' => 'integer',
            'actual_total_tokens' => 'integer',
            'estimated_provider_cost' => 'decimal:9',
            'estimated_user_cost' => 'decimal:9',
            'actual_provider_cost' => 'decimal:9',
            'actual_user_cost' => 'decimal:9',
            'applied_markup_rate' => 'decimal:4',
            'image_url_expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    /**
     * The user that initiated this generation.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The webhook events received for this generation from the provider.
     *
     * @return HasMany<WebhookEvent, $this>
     */
    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class, 'provider_generation_id', 'provider_generation_id');
    }

    /**
     * Scope a query to only include queued generations.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeQueued(Builder $query): void
    {
        $query->where('status', GenerationStatus::Queued);
    }

    /**
     * Scope a query to only include processing generations.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeProcessing(Builder $query): void
    {
        $query->where('status', GenerationStatus::Processing);
    }

    /**
     * Scope a query to only include succeeded generations.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeSucceeded(Builder $query): void
    {
        $query->where('status', GenerationStatus::Succeeded);
    }

    /**
     * Scope a query to only include failed generations.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeFailed(Builder $query): void
    {
        $query->where('status', GenerationStatus::Failed);
    }

    /**
     * Scope a query to only include settled generations.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeSettled(Builder $query): void
    {
        $query->whereNotNull('settled_at');
    }

    /**
     * Scope a query to only include unsettled generations.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeUnsettled(Builder $query): void
    {
        $query->whereNull('settled_at');
    }

    /**
     * Scope a query to a specific user.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForUser(Builder $query, User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $query->where('user_id', $userId);
    }

    /**
     * Check if the generation has reached a completed (succeeded or failed) state.
     */
    public function isCompleted(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Check if the generation succeeded.
     */
    public function isSucceeded(): bool
    {
        return $this->status === GenerationStatus::Succeeded;
    }

    /**
     * Check if the generation failed.
     */
    public function isFailed(): bool
    {
        return $this->status === GenerationStatus::Failed;
    }

    /**
     * Check if the financial settlement has been completed.
     */
    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }
}
