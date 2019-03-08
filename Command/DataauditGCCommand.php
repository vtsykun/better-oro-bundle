<?php

declare(strict_types=1);

namespace Okvpn\Bundle\BetterOroBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataauditGCCommand extends Command implements CronCommandInterface
{
    private $registry;
    private $configuration;

    public function __construct(array $configuration, ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->configuration = $configuration;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:cron:dataaudit-garbage-collector')
            ->setDescription(
                'Removes old records from the oro_audit table to prevent an infinite grown of table size.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        array_map([$this, 'processConfiguration'], $this->configuration);
    }

    protected function processConfiguration(array $config)
    {
        $em = $this->registry->getManager();
        $filter = $config['entity_class'];
        $doctrineAllMetadata = array_filter(
            $em->getMetadataFactory()->getAllMetadata(),
            function (ClassMetadataInfo $info) use ($filter) {
                return preg_match('/' . str_replace('\\', '\\\\', $filter) . '/', $info->getName());
            }
        );

        $classes = array_map(function (ClassMetadataInfo $info) {
            return $info->getName();
        }, $doctrineAllMetadata);

        $loggedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $loggedAt->setTimestamp(time() - $config['keep_time']);

        $qb = $this->getManager()->createQueryBuilder();
        $qb->delete('OroDataAuditBundle:Audit', 'a')
            ->where('a.loggedAt < :loggedAt')
            ->andWhere('a.objectClass IN (:objectClasses)')
            ->andWhere('a.action = :action')
            ->setParameter('action', $config['action'])
            ->setParameter('objectClasses', $classes)
            ->setParameter('loggedAt', $loggedAt);

        $qb->getQuery()->execute();
    }

    /**
     * @return EntityManagerInterface|object
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass(Audit::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '30 3 * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return !empty($this->configuration);
    }
}
