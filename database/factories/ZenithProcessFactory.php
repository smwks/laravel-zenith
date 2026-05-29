<?php

namespace SMWks\LaravelZenith\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SMWks\LaravelZenith\Models\ZenithProcess;

class ZenithProcessFactory extends Factory
{
    protected $model = ZenithProcess::class;

    public function definition(): array
    {
        return [
            'type' => 'supervisor',
            'name' => 'default',
            'pid' => $this->faker->numberBetween(1000, 9999),
            'hostname' => 'localhost',
            'queue' => 'default',
            'connection' => 'database',
            'started_at' => now(),
            'last_heartbeat_at' => now(),
            'status' => 'idle',
            'metadata' => ['balance' => 'manual'],
        ];
    }

    public function fixedBalance(): static
    {
        return $this->state(['metadata' => ['balance' => 'fixed']]);
    }

    public function automaticBalance(): static
    {
        return $this->state(['metadata' => ['balance' => 'automatic']]);
    }
}
