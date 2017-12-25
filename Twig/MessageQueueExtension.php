<?php

namespace Okvpn\Bundle\BetterOroBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;

class MessageQueueExtension extends \Twig_Extension
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('render_job_log', [$this, 'renderJobLog'], [
                'needs_environment' => true,
                'is_safe' => ['html']
            ])
        ];
    }

    /**
     * @param \Twig_Environment $environment
     * @param Job $job
     * @return string
     */
    public function renderJobLog(\Twig_Environment $environment, Job $job)
    {
        $logs = $this->registry->getRepository('OkvpnBetterOroBundle:JobLog')
            ->findBy(['job' => $job->getId()], ['id' => 'DESC']);

        return $environment->render('OkvpnBetterOroBundle:MessageQueue:job_log.html.twig', [
            'logs' => $logs
        ]);
    }
}
