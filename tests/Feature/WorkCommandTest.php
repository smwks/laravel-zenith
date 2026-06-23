<?php

use Illuminate\Console\Command;
use SMWks\LaravelZenith\Commands\WorkCommand;
use SMWks\LaravelZenith\Models\ZenithProcess;
use Symfony\Component\Console\Input\ArrayInput;

function invokeRegisterSupervisor(): void
{
    $command = new WorkCommand();
    $command->setLaravel(app());

    $input = new ArrayInput([], $command->getDefinition());
    (new ReflectionProperty(Command::class, 'input'))->setValue($command, $input);

    (new ReflectionMethod($command, 'registerSupervisor'))
        ->invoke($command, 'fixed', 1, 1, null, 'database');
}

it('marks stale processes from other hostnames as abandoned on startup', function () {
    $heartbeatInterval = config('zenith.heartbeat_interval', 30);
    $staleAt = now()->subSeconds($heartbeatInterval * 2 + 1);

    $staleIdle = ZenithProcess::factory()->create([
        'hostname' => 'dead-pod-xyz',
        'status' => 'idle',
        'last_heartbeat_at' => $staleAt,
    ]);
    $staleWorking = ZenithProcess::factory()->create([
        'hostname' => 'dead-pod-xyz',
        'status' => 'working',
        'last_heartbeat_at' => $staleAt,
    ]);
    $alreadyTerminated = ZenithProcess::factory()->create([
        'hostname' => 'dead-pod-xyz',
        'status' => 'terminated',
        'last_heartbeat_at' => $staleAt,
    ]);

    invokeRegisterSupervisor();

    expect($staleIdle->fresh()->status)->toBe('abandoned');
    expect($staleWorking->fresh()->status)->toBe('abandoned');
    expect($alreadyTerminated->fresh()->status)->toBe('terminated');
});

it('does not terminate processes from other hostnames with a recent heartbeat', function () {
    $liveProcess = ZenithProcess::factory()->create([
        'hostname' => 'live-pod-abc',
        'status' => 'idle',
        'last_heartbeat_at' => now(),
    ]);
    $currentHostProcess = ZenithProcess::factory()->create([
        'hostname' => gethostname(),
        'status' => 'idle',
        'last_heartbeat_at' => now(),
    ]);

    invokeRegisterSupervisor();

    expect($liveProcess->fresh()->status)->toBe('idle');
    expect($currentHostProcess->fresh()->status)->toBe('idle');
});
