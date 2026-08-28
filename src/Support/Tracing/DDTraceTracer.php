<?php

namespace SMWks\LaravelZenith\Support\Tracing;

use Throwable;

class DDTraceTracer implements Tracer
{
    protected bool $spanOpen = false;

    public function startSpan(string $name, string $resource, array $meta = []): void
    {
        $span = \DDTrace\start_span();
        $span->name = $name;
        $span->resource = $resource;

        foreach ($meta as $key => $value) {
            $span->meta[$key] = (string) $value;
        }

        $this->spanOpen = true;
    }

    public function tagError(Throwable $exception): void
    {
        if (! $this->spanOpen) {
            return;
        }

        $span = \DDTrace\active_span();
        $span->meta['error.type'] = get_class($exception);
        $span->meta['error.message'] = $exception->getMessage();
        $span->meta['error.stack'] = $exception->getTraceAsString();
    }

    public function closeSpan(): void
    {
        if (! $this->spanOpen) {
            return;
        }

        \DDTrace\close_span();
        $this->spanOpen = false;
    }
}
