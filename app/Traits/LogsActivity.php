<?php

namespace App\Traits;

use App\Models\UserActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
    protected static function log($event, $model)
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $before = null;
            $after  = null;

            if ($event === 'created') {
                $after = $model->getAttributes();
            } elseif ($event === 'updated') {
                $before = array_intersect_key($model->getOriginal(), $model->getChanges());
                $after  = $model->getChanges();
            } elseif ($event === 'deleted') {
                $before = $model->getOriginal();
            }

            // 🔹 Bagian cari controller atau file pemanggil
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

            $caller = collect($trace)->first(function ($t) {
                return isset($t['class']) && str_contains($t['class'], 'App\\Http\\Controllers');
            });

            if (!$caller) {
                // fallback: cari file dalam folder app/Http
                $caller = collect($trace)->first(function ($t) {
                    return isset($t['file']) && str_contains($t['file'], base_path('app/Http'));
                });
            }

            $source = null;
            if ($caller) {
                if (isset($caller['class'])) {
                    $source = $caller['class'] . '@' . ($caller['function'] ?? '') . ' (line ' . ($caller['line'] ?? '-') . ')';
                } elseif (isset($caller['file'])) {
                    $source = str_replace(base_path() . '/', '', $caller['file']) . ' (line ' . ($caller['line'] ?? '-') . ')';
                }
            }

            info('DEBUG LOG', [
                'event' => $event,
                'model' => class_basename($model),
                'connection' => $model->getConnectionName(),
                'before' => $before,
                'after' => $after
            ]);
            // 🔹 Simpan log ke tabel
            UserActivity::create([
                'user_id'     => Auth::id() ?? null,
                'action'      => class_basename($model) . ' ' . $event,
                'description' => json_encode([
                    'before' => $before,
                    'after'  => $after,
                ], JSON_UNESCAPED_UNICODE),
                'ip_address'  => request()?->ip(),
                'user_agent'  => request()?->header('User-Agent'),
                'source'      => $source ?? null, // pastikan kolom ini nullable
            ]);
        } catch (\Throwable $e) {
            info('Failed to log user activity', [
                'event' => $event,
                'model' => class_basename($model),
                'message' => $e->getMessage(),
            ]);
        }
    }


    // protected static function log($event, $model)
    // {

    //     // Hanya log saat bukan di console (misal queue, seeder, dll bisa skip)
    //     if (app()->runningInConsole()) {
    //         return;
    //     }


    //     try {
    //         $before = null;
    //         $after  = null;

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
}
