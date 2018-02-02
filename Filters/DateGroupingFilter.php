<?php

namespace Okvpn\Bundle\BetterOroBundle\Filters;

use Doctrine\ORM\Query\Expr\Select;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Filter\DateGroupingFilter as BugDateGroupingFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class DateGroupingFilter extends BugDateGroupingFilter
{
    public function applyOrderBy(OrmDatasource $datasource, String $sortKey, String $direction)
    {
        /* @var OrmDatasource $datasource */
        $qb = $datasource->getQueryBuilder();
        $added = false;

        foreach ([self::TYPE_YEAR, self::TYPE_QUARTER, self::TYPE_MONTH, self::TYPE_DAY] as $groupBy) {
            $columnName = $this->getSelectAlias($groupBy);
            $groupingName = $this->getSelectClause($groupBy);
            $partName = sprintf('%s as %s', $groupingName, $columnName);

            /** @var Select $select */
            foreach ($qb->getDQLPart('select') as $select) {
                foreach ($select->getParts() as $part) {
                    if ($partName === $part) {
                        $qb->addOrderBy($columnName, $direction);
                        $added = true;
                    }
                }
            }
        }

        if (!$added) {
            $qb->addOrderBy($sortKey, $direction);
        }
    }

    /**
     * @param string $postfix
     * @return string
     */
    private function getSelectAlias($postfix)
    {
        return $this->get(self::COLUMN_NAME) . ucfirst($postfix);
    }

    /**
     * @param string $groupBy
     * @return string
     */
    private function getSelectClause($groupBy)
    {
        return sprintf('%s(%s)', $groupBy, $this->get(FilterUtility::DATA_NAME_KEY));
    }
}
