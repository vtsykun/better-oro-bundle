<?php

namespace Okvpn\Bundle\BetterOroBundle\DependencyInjection;

use Okvpn\Bundle\BetterOroBundle\Command\DataauditGCCommand;
use Okvpn\Bundle\BetterOroBundle\DependencyInjection\CompilerPass\MessageQueuePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class OkvpnBetterOroExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $capabilities = $config['capabilities'];

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $container->setParameter('okvpn.better_oro', $capabilities);

        if (true === $capabilities['mq_send_events']) {
            $loader->load('mq_send_events.yml');
            $priorityListener = $container->getDefinition('okvpn.message_queue.listener.default_priority');
            $priorityListener->addMethodCall('setPriorityTopicMapping', [$config['default_priorities']]);
        }

        if (true === $capabilities['job_logs']) {
            $loader->load('job_logs.yml');
            if ($container->hasParameter('monolog.additional_channels')) {
                $channels = $container->getParameter('monolog.additional_channels');
            } else {
                $channels = [];
            }
            $channels[] = MessageQueuePass::CHANEL;
            $container->setParameter('monolog.additional_channels', $channels);
        }

        $dataaudit = $config['dataaudit'];
        $container->getDefinition(DataauditGCCommand::class)
            ->replaceArgument(0, $dataaudit['dataaudit_gc'] ?? []);
        if (!isset($dataaudit['default_organization'])) {
            $container->removeDefinition('okvpn.dataaudit.token_storage');
        } else {
            $container->getDefinition('okvpn.dataaudit.token_storage')
                ->replaceArgument(1, (int) $dataaudit['default_organization']);
        }

        if (true === $capabilities['mq_disable_container_reset']) {
            $loader->load('reset_extension.yml');
        }

        if (true === $capabilities['fix_calendar']) {
            $loader->load('fix_calendar.yml');
        }

        if (true === $capabilities['disable_remote_transactions']) {
            $loader->load('remote_transactions.yml');
        }
    }
}
