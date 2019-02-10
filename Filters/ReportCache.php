<?php

declare(strict_types=1);

namespace Okvpn\Bundle\BetterOroBundle\Filters;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ReportBundle\Entity\Report;

/**
 * Memory cache for report. Used to disable serialize grid
 * configuration, because in PHP closure can not be serialized.
 *
 * In order to fix bug that introduce here
 * @see https://github.com/oroinc/platform/commit/6cc4fccd22d5567fcfa09ac5c9aef181c61c01bc#diff-6814c40a88f752717b2151e7ebe9820d
 */
class ReportCache implements Cache, FlushableCache, ClearableCache
{
    protected $cache = [];
    protected $registry;


    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        $hash = $this->getReportHashByCacheKey($id);

        if (isset($this->cache[$id])) {
            list($data, $oldHash, $lifeTime) = $this->cache[$id];
            if (($lifeTime !== 0 && $lifeTime < time()) || $hash === null || $oldHash === null || $hash !== $oldHash) {
                return false;
            }

            return $data;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->fetch($id) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        $hash = $this->getReportHashByCacheKey($id);

        if ($lifeTime !== 0) {
            $lifeTime += time();
        }

        $this->cache[$id] = [$data, $hash, $lifeTime];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        unset($this->cache[$id]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return null;
    }

    protected function getReportHashByCacheKey($id)
    {
        $report = null;
        if (preg_match('#.+_(\d+)$#', $id, $match) and isset($match[1])) {
            $report = $this->registry->getRepository(Report::class)
                ->find((int) $match[1]);
        }

        if ($report instanceof Report) {
            $date = $report->getUpdatedAt() ?: new \DateTime('now');
            $report = sha1($report->getDefinition() . $date->format('c'));
        }

        return $report;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $this->cache = [];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        $this->cache = [];
        return true;
    }
}
