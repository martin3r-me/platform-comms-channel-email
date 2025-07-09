<?php

namespace Martin3r\LaravelActivityLog\Traits;

use Illuminate\Database\Eloquent\Model;
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
     */
    public static function bootLogsActivity(): void
    {
        $events = static::$recordEvents ?: config('activity-log.events', []);

        foreach ($events as $event) {
            static::$event(function (Model $model) use ($event) {
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
        // Decide which attributes to inspect (all vs. only changes)
        $attributes = $event === 'updated'
            ? $this->getChanges()
            : $this->getAttributes();

        // Merge global and model-specific ignored attributes
        $ignore = array_merge(
            config('activity-log.ignore_attributes', []),
            $this->ignoreAttributes
        );

        // Filter out ignored attributes
        return collect($attributes)
            ->except($ignore)
            ->toArray();
    }
}
