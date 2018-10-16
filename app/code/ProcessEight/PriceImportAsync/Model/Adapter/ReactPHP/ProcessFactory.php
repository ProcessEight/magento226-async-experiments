<?php
/**
 * ProcessEight
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact ProcessEight for more information.
 *
 * @package     ProcessEight\CatalogImageResizeAsync
 * @copyright   Copyright (c) 2018 ProcessEight
 * @author      ProcessEight
 *
 */

namespace ProcessEight\PriceImportAsync\Model\Adapter\ReactPHP;

use React\ChildProcess\Process;

/**
 * Class ProcessFactory
 *
 * @package ProcessEight\CatalogImageResizeAsync\Model\Adapter\ReactPHP
 */
class ProcessFactory
{
    /**
     * @param string $command
     *
     * @return Process
     */
    public function create(string $command) : Process
    {
        return new Process($command);
    }
}
