<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Controller;

use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Service\SleepingCounter;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Service\SleepingCounterChecker;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class SleepController
{
    private SleepingCounter $sleepingCounter;

    private SleepingCounterChecker $checker;

    public function __construct(SleepingCounter $sleepingCounter, SleepingCounterChecker $checker)
    {
        $this->sleepingCounter = $sleepingCounter;
        $this->checker = $checker;
    }

    /**
     * @Route(
     *     methods={"GET"},
     *     path="/sleep"
     * )
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function index()
    {
        $this->sleepingCounter->sleepAndCount();
        $counter = $this->sleepingCounter->getCounter();
        $check = $this->checker->wasChecked() ? 'true' : 'false';

        return new Response(
            "<html><body>Sleep was fine. Count was {$counter}. Check was {$check}.</body></html>"
        );
    }
}
