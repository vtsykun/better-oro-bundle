<?php

namespace Okvpn\Bundle\BetterOroBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Twig\DataGridExtension as BaseDataGridExtension;

class DataGridExtension extends BaseDataGridExtension
{
    /**
     * @var bool
     */
    protected $kernelDebug;

    /**
     * Renders grid data
     *
     * @param DatagridInterface $grid
     *
     * @return array
     */
    public function getGridData(DatagridInterface $grid)
    {
        try {
            return $grid->getData()->toArray();
        } catch (\Exception $e) {
            $this->getLogger()->error('Getting grid data failed.', ['exception' => $e]);
            if (true === $this->container->getParameter('kernel.debug')) {
                throw $e;
            }

            return [
                "data"     => [],
                "metadata" => [],
                "options"  => []
            ];
        }
    }
}
