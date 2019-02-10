<?php

namespace Okvpn\Bundle\BetterOroBundle\Tag;

use Oro\Bundle\TagBundle\Grid\Extension\TagSearchResultsExtension as OroTagSearchResultsExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item as ResultItem;

/**
 * Fix exception on page /tag/search/7?from=
 */
class TagSearchResultsExtension extends OroTagSearchResultsExtension
{
    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $rows = $result->getData();

        $mappingConfig = $this->mapper->getMappingConfig();

        $rows = array_map(
            function (ResultRecordInterface $record) use ($mappingConfig) {
                $entityClass = $record->getValue('entityName');
                $entityId    = $record->getValue('recordId');

                $entityConfig = array_key_exists($entityClass, $mappingConfig)
                    ? $entityConfig = $this->mapper->getEntityConfig($entityClass)
                    : [];

                return new ResultItem(
                    $entityClass,
                    $entityId,
                    null,
                    null,
                    $entityConfig
                );
            },
            $rows
        );

        $entities = $this->resultFormatter->getResultEntities($rows);

        $resultRows = [];
        /** @var ResultItem $item */
        foreach ($rows as $item) {
            $entityClass = $item->getEntityName();
            $entityId    = $item->getRecordId();
            if (!isset($entities[$entityClass][$entityId])) {
                continue;
            }

            $entity      = $entities[$entityClass][$entityId];
            $this->dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item, $entity));
            $resultRows[] = new ResultRecord(['entity' => $entity, 'indexer_item' => $item]);
        }

        $result->setData($resultRows);
    }
}
