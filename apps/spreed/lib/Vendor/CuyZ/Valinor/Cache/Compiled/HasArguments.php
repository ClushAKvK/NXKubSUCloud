<?php

declare(strict_types=1);

namespace OCA\Talk\Vendor\CuyZ\Valinor\Cache\Compiled;

/** @internal */
interface HasArguments extends CacheCompiler
{
    /**
     * @return array<string, mixed>
     */
    public function arguments(): array;
}
