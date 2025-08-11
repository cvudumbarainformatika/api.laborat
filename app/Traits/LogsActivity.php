<?php

namespace App\Traits;

use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            self::log('created', $model);
        });

        static::updated(function ($model) {
            self::log('updated', $model);
        });

        static::deleted(function ($model) {
            self::log('deleted', $model);
        });
    }
    protected static function log(string $event, Model $model): void
    {
        if (app()->runningInConsole() && !config('logging.log_console_actions', false)) {
            return;
        }

        try {
            $changes = self::getModelChanges($event, $model);
            $source = self::getActionSource();

            UserActivity::create([
                'user_id'     => auth()->user()->id ?? null,
                'action'      => class_basename($model) . ' ' . $event,
                'description' => json_encode($changes, JSON_UNESCAPED_UNICODE),
                'ip_address'  => request()?->ip(),
                'user_agent'  => request()?->header('User-Agent'),
                'source'      => $source ?? null,
            ]);
        } catch (\Throwable $e) {
            report($e); // Use Laravel's error reporting
            info('Failed to log user activity', [
                'event' => $event,
                'model' => class_basename($model),
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected static function getModelChanges(string $event, Model $model): array
    {
        $before = null;
        $after = null;

        switch ($event) {
            case 'created':
                $after = $model->getAttributes();
                break;
            case 'updated':
                $before = array_intersect_key($model->getOriginal(), $model->getChanges());
                $after = $model->getChanges();
                break;
            case 'deleted':
                $before = $model->getOriginal();
                break;
        }

        return [
            'before' => $before,
            'after' => $after,
        ];
    }

    protected static function getActionSource(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        // Look for controller first
        $caller = collect($trace)->first(function ($t) {
            return isset($t['class']) && str_contains($t['class'], 'App\\Http\\Controllers');
        });

        // Fallback to any app/Http file
        if (!$caller) {
            $caller = collect($trace)->first(function ($t) {
                return isset($t['file']) && str_contains($t['file'], base_path('app/Http'));
            });
        }

        if (!$caller) {
            return null;
        }

        return isset($caller['class'])
            ? $caller['class'] . '@' . ($caller['function'] ?? '') . ' (line ' . ($caller['line'] ?? '-') . ')'
            : str_replace(base_path() . '/', '', $caller['file']) . ' (line ' . ($caller['line'] ?? '-') . ')';
    }
}
    //         if ($event === 'created') {
    //             $after = $model->getAttributes();
    //         } elseif ($event === 'updated') {
    //             $before = array_intersect_key($model->getOriginal(), $model->getChanges());
    //             $after  = $model->getChanges();
    //         } elseif ($event === 'deleted') {
    //             $before = $model->getOriginal();
    //         }

    //         UserActivity::create([
    //             'user_id'     => Auth::id(),
    //             'action'      => class_basename($model) . ' ' . $event,
    //             'description' => json_encode([
    //                 'before' => $before,
    //                 'after'  => $after,
    //             ], JSON_UNESCAPED_UNICODE),
    //             'ip_address'  => request()?->ip(),
    //             'user_agent'  => request()?->header('User-Agent'),
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::warning('Failed to log user activity', [
    //             'event' => $event,
    //             'model' => class_basename($model),
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }
// }
