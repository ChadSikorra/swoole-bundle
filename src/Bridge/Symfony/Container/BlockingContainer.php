<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container;

use Co;
use Symfony\Component\DependencyInjection\Container;

class BlockingContainer extends Container
{
    /**
     * @var array<string, int>
     */
    private $concurrentResolving = [];

    /**
     * {@inheritDoc}
     */
    public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        $cid = Co::getCid();

        // wait 0.01 ms if the container is already resolving the requested service
        // coroutine hook for usleep should switch context to other coroutine
        while (isset($this->concurrentResolving[$id]) && $this->concurrentResolving[$id] !== $cid) {
            usleep(10);
        }

        try {
            $this->concurrentResolving[$id] = $cid;
            $service = parent::get($id, $invalidBehavior);
        } finally {
            unset($this->concurrentResolving[$id]);
        }

        return $service;
    }
}
