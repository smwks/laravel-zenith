<?php

namespace SMWks\LaravelZenith\Support\Tracing;

use Throwable;

interface Tracer
{
    public function startSpan(string $name, string $resource, array $meta = []): void;

    public function tagError(Throwable $exception): void;

    public function closeSpan(): void;
}
