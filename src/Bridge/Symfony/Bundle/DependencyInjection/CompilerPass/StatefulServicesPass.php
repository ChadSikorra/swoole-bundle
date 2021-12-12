<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass;

use K911\Swoole\Bridge\Doctrine\DoctrineProcessor;
use K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\StatefulServices\CompileProcessor;
use K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\StatefulServices\Proxifier;
use K911\Swoole\Bridge\Symfony\Container\BlockingContainer;
use K911\Swoole\Bridge\Symfony\Container\ServicePoolContainer;
use K911\Swoole\Bridge\Symfony\Container\StabilityChecker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UnexpectedValueException;

final class StatefulServicesPass implements CompilerPassInterface
{
    private const IGNORED_SERVICES = [
        ServicePoolContainer::class => true,
        BlockingContainer::class => true,
    ];

    private const MANDATORRY_SERVICES_TO_PROXIFY = [
        'annotations.reader' => null,
    ];

    private const COMPILE_PROCESSORS = [
        DoctrineProcessor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('swoole_bundle.coroutines_support.enabled')) {
            return;
        }

        if (!$container->getParameter('swoole_bundle.coroutines_support.enabled')) {
            return;
        }

        /** @var array<string, null|array<string, mixed>> $servicesToProxify */
        $servicesToProxify = $container->findTaggedServiceIds('kernel.reset');
        $servicesToProxify = array_merge($servicesToProxify, self::MANDATORRY_SERVICES_TO_PROXIFY);

        /** @var array<class-string, class-string<StabilityChecker>> $stabilityCheckers */
        $stabilityCheckers = $container->getParameter('swoole_bundle.coroutines_support.stability_checkers');

        if (!is_array($stabilityCheckers)) {
            throw new UnexpectedValueException('Invalid compiler processors provided');
        }

        $proxifier = new Proxifier($container, $stabilityCheckers);

        foreach (array_keys($servicesToProxify) as $serviceId) {
            if (isset(self::IGNORED_SERVICES[$serviceId])) {
                continue;
            }

            if (!$container->has($serviceId)) {
                continue;
            }

            $proxifier->proxifyService($serviceId);
        }

        /** @var array<class-string<CompileProcessor>> $compileProcessors */
        $compileProcessors = $container->getParameter('swoole_bundle.coroutines_support.compile_processors');

        if (!is_array($compileProcessors)) {
            throw new UnexpectedValueException('Invalid compiler processors provided');
        }

        $compileProcessors = array_merge(self::COMPILE_PROCESSORS, $compileProcessors);

        foreach ($compileProcessors as $processorClass) {
            /** @var CompileProcessor $processor */
            $processor = new $processorClass();
            $processor->process($container, $proxifier);
        }

        $poolContainerDef = $container->findDefinition(ServicePoolContainer::class);
        $poolContainerDef->setArgument(0, $proxifier->getProxifiedServicePoolsRefs());
        $poolContainerDef->setArgument(1, $proxifier->getResetterRefs());
    }
}
