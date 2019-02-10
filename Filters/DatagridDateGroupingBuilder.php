<?php

namespace Okvpn\Bundle\BetterOroBundle\Filters;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\Bundle\ReportBundle\Grid\DatagridDateGroupingBuilder as OroDatagridDateGroupingBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fix bug that introduce in BAP-17910: Incorrect processing of report field values
 * @see https://github.com/oroinc/platform/commit/6cc4fccd22d5567fcfa09ac5c9aef181c61c01bc#diff-6814c40a88f752717b2151e7ebe9820d
 */
class DatagridDateGroupingBuilder extends OroDatagridDateGroupingBuilder
{
    protected $container;

    public function __construct(string $calendarDateEntity, ?JoinIdentifierHelper $joinIdHelper = null, ?ContainerInterface $container = null)
    {
        parent::__construct($calendarDateEntity, $joinIdHelper);
        $this->container = $container;
    }

    /**
     * Configures sorter section for newly added date grouping columns
     *
     * @param DatagridConfiguration $config
     */
    protected function changeSortersSection(DatagridConfiguration $config)
    {
        $sorters = $config->offsetGet(static::SORTERS_KEY_NAME);
        $sorters['columns'][static::CALENDAR_DATE_GRID_COLUMN_NAME] = [
            'data_name' => static::CALENDAR_DATE_GRID_COLUMN_NAME,
            'apply_callback' => [$this, 'applyOrderBy']
        ];
        if (!array_key_exists('default', $sorters)) {
            $sorters['default'] = [];
        }
        $sorters['default'][static::CALENDAR_DATE_GRID_COLUMN_NAME] = AbstractSorterExtension::DIRECTION_DESC;
        $config->offsetSet(static::SORTERS_KEY_NAME, $sorters);
    }

    /**
     * Fix bug after remove
     */
    public function applyOrderBy()
    {
        return $this->container->get('oro_filter.date_grouping_filter')
            ->applyOrderBy(...func_get_args());
    }
}
