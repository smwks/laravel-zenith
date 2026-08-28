<?php

namespace SMWks\LaravelZenith\Support\Tracing;

use Throwable;

class NullTracer implements Tracer
{
    public function startSpan(string $name, string $resource, array $meta = []): void {}

    public function tagError(Throwable $exception): void {}

    public function closeSpan(): void {}
}
