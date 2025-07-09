<?php

namespace Martin3r\LaravelActivityLog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Martin3r\LaravelActivityLog\Models\Activity;

trait LogsActivity
{
    /**
     * Model-scoped override for the events to record.
     * e.g. protected static array $recordEvents = ['created', 'updated', 'deleted'];
     * If empty, falls back to config('activity-log.events').
     *
     * @var string[]
     */
    protected static array $recordEvents = [];

    /**
     * Instance-scoped attributes to ignore when recording changes.
     * e.g. protected array $ignoreAttributes = ['password', 'remember_token'];
     * Merged with config('activity-log.ignore_attributes').
     *
     * @var string[]
     */
    protected array $ignoreAttributes = [];

    /**
     * Boot the LogsActivity trait and register model event listeners.
     * Logs trait initialization for each model using it.
     */
    public static function bootLogsActivity(): void
    {
        // Log trait initialization
        Log::info('LogsActivity trait initialized for model', [
            'model' => static::class,
        ]);

        $events = static::$recordEvents ?: config('activity-log.events', []);

        foreach ($events as $event) {
            static::{$event}(function (Model $model) use ($event) {
                Log::info("Activity event fired: {$event}", [
                    'model' => get_class($model),
                    'id'    => $model->getKey(),
                ]);

                $model->logActivity($event);
            });
        }
    }

    /**
     * Polymorphic activities relation with latest ordering.
     */
    public function activities()
    {
        return $this->morphMany(Activity::class, 'activityable')->latest();
    }

    /**
     * Create a new activity record for the given event.
     */
    public function logActivity(string $event): void
    {
        $properties = $this->getActivityProperties($event);

        // Skip recording if no meaningful changes on update
        if ($event === 'updated' && empty($properties)) {
            return;
        }

        Log::info("Logging activity: {$event}", [
            'model'      => get_class($this),
            'id'         => $this->getKey(),
            'properties' => $properties,
        ]);

        $this->activities()->create([
            'name'       => $event,
            'user_id'    => auth()->id(),
            'properties' => $properties,
        ]);
    }

    /**
     * Gather the properties to save for the activity.
     */
    protected function getActivityProperties(string $event): array
    {
        $attributes = $event === 'updated'
            ? $this->getChanges()
            : $this->getAttributes();

        $ignore = array_merge(
            config('activity-log.ignore_attributes', []),
            $this->ignoreAttributes
        );

        return collect($attributes)
            ->except($ignore)
            ->toArray();
    }
}