<?php

namespace Okvpn\Bundle\BetterOroBundle\Command;

use Okvpn\Bundle\BetterOroBundle\Cron\CronLoadEvent;
use Psr\Log\LogLevel;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Command\CronDefinitionsLoadCommand as BugCronDefinitionsLoadCommand;

/**
 * todo: Exist for bugfix. Should be remove when https://github.com/orocrm/platform/pull/673 will merge.
 */
class CronDefinitionsLoadCommand extends BugCronDefinitionsLoadCommand
{
    /** @var DeferredScheduler */
    private $deferred;

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $capabilities = $this->getContainer()->getParameter('okvpn.better_oro');
        if (false === $capabilities['cron_fix_cleanup']) {
            return parent::execute($input, $output);
        }

        $deferred = $this->getDeferredScheduler($output);
        $this->removeOrphanedCronCommands($deferred);
        $this->loadCronCommands($deferred);

        $event = new CronLoadEvent($deferred);
        $this->getContainer()->get('event_dispatcher')->dispatch(CronLoadEvent::NAME, $event);

        $deferred->flush();
        $this->deferred = null;

        $output->writeln('<info>The cron command definitions were successfully loaded.</info>');
        return 0;
    }

    /**
     * @param DeferredScheduler $deferredScheduler
     */
    private function removeOrphanedCronCommands(DeferredScheduler $deferredScheduler)
    {
        $schedulesForDelete = array_filter(
            $this->getRepository('OroCronBundle:Schedule')->findAll(),
            function (Schedule $schedule) {
                try {
                    $command = $this->getApplication()->get($schedule->getCommand());
                    if ($command instanceof CronCommandInterface &&
                        $command->getDefaultDefinition() !== $schedule->getDefinition() &&
                        preg_match('/^oro:cron/', $schedule->getCommand())
                    ) {
                        return true;
                    }
                } catch (CommandNotFoundException $e) {
                    return true;
                }

                return false;
            }
        );

        /** @var Schedule $schedule */
        foreach ($schedulesForDelete as $schedule) {
            $deferredScheduler->removeSchedule(
                $schedule->getCommand(),
                $schedule->getArguments(),
                $schedule->getDefinition()
            );
        }
    }

    /**
     * @param DeferredScheduler $deferredScheduler
     */
    private function loadCronCommands(DeferredScheduler $deferredScheduler)
    {
        $cronCommands = $this->getApplication()->all('oro:cron');
        foreach ($cronCommands as $command) {
            if ($command instanceof CronCommandInterface &&
                $command->getDefaultDefinition()
            ) {
                $deferredScheduler->addSchedule(
                    $command->getName(),
                    [],
                    $command->getDefaultDefinition()
                );
            }
        }
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    private function getEntityManager($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    private function getRepository($className)
    {
        return $this->getEntityManager($className)->getRepository($className);
    }

    /**
     * @param OutputInterface $output
     * @return DeferredScheduler
     */
    private function getDeferredScheduler(OutputInterface $output)
    {
        if (null === $this->deferred) {
            $logger = new ConsoleLogger($output, [
                LogLevel::EMERGENCY => OutputInterface::VERBOSITY_QUIET,
                LogLevel::ALERT => OutputInterface::VERBOSITY_QUIET,
                LogLevel::CRITICAL => OutputInterface::VERBOSITY_QUIET,
                LogLevel::ERROR => OutputInterface::VERBOSITY_QUIET,
                LogLevel::WARNING => OutputInterface::VERBOSITY_QUIET,
                LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::DEBUG => OutputInterface::VERBOSITY_NORMAL,
            ]);

            $this->deferred = $this->getContainer()->get('oro_cron.deferred_scheduler');
            $this->deferred->setLogger($logger);
        }

        return $this->deferred;
    }
}
