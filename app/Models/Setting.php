<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasUuids;
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): void
    {
        $stringValue = self::encodeValue($value, $type);

        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        Cache::forget("setting.{$key}");
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Cast value based on type
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Encode value for storage
     */
    protected static function encodeValue(mixed $value, string $type): string
    {
        return match ($type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }
}
