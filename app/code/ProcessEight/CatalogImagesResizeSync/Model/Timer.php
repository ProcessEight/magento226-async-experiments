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
 * @package     ProcessEight\CatalogImagesResizeSync
 * @copyright   Copyright (c) 2018 ProcessEight
 * @author      ProcessEight
 *
 */

declare(strict_types=1);

namespace ProcessEight\CatalogImagesResizeSync\Model;

use ProcessEight\CatalogImagesResizeSync\Api\TimerInterface;
use ProcessEight\CatalogImagesResizeSync\Exception\TimerException;

/**
 * Class Timer
 *
 * @package ProcessEight\CatalogImagesResizeSync\Model
 */
class Timer implements TimerInterface
{
    /**
     * @var float
     */
    private $timeStart = 0.0;

    /**
     * @var float
     */
    private $timeStop = 0.0;

    /**
     * @return void
     */
    public function startTimer() : void
    {
        $this->timeStart = microtime(true);
    }

    /**
     * @return void
     * @throws TimerException
     */
    public function stopTimer() : void
    {
        if (!$this->timeStart) {
            throw new TimerException('Timer not started');
        }
        $this->timeStop = microtime(true);
    }

    /**
     * @return float
     * @throws TimerException
     */
    public function getExecutionTimeInSeconds() : float
    {
        if (!$this->timeStart || !$this->timeStop) {
            throw new TimerException('Execution time cannot be calculated');
        }
        $executionTime   = $this->timeStop - $this->timeStart;
        $this->timeStart = 0.0;
        $this->timeStop  = 0.0;

        return $executionTime;
    }
}
