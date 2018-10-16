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
 * @package     ProcessEight\PriceImportAsync
 * @copyright   Copyright (c) 2018 ProcessEight
 * @author      ProcessEight
 *
 */

declare(strict_types=1);

namespace ProcessEight\PriceImportAsync\Api;

use ProcessEight\PriceImportAsync\Exception\TimerException;

/**
 * Interface TimerInterface
 * @package ProcessEight\PriceImportAsync\Api
 */
interface TimerInterface
{
    /**
     * @return void
     */
    public function startTimer(): void;

    /**
     * @return void
     * @throws TimerException
     */
    public function stopTimer(): void;

    /**
     * @return float
     * @throws TimerException
     */
    public function getExecutionTimeInSeconds(): float;
}
