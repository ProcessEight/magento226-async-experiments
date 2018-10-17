# ProcessEight PriceImportSync

## Description

Command which updates catalog product prices in a synchronous fashion

## Execution

```bash
# Base prices only
$ mr2 processeight:catalog:prices:import:sync
Starting timer...
2046/0 [-->-------------------------]   0% 1 sec 54.0 MiB 	| WSH12
Stopped timer.
2045 prices imported successfully in 0.4961941242218 seconds.

# Base prices and cost prices only
$ mr2 processeight:catalog:prices:import:sync 
Starting timer...
2048/0 [---->-----------------------]   0% 1 sec 54.0 MiB 	| WSH12
Stopped timer.
2046 base prices and 2046 cost prices imported successfully in 0.57457995414734 seconds.
```

## Timer

Inject the `TimerInterface` using DI. Then:

```php
<?php

// Start the timer
$this->timer->startTimer();

// Do something
$customerIds = [12345, 12346, 12347];
$numberOfChildProcesses = 3;
$this->startProcesses($customerIds, $numberOfChildProcesses);

// Stop the timer
$this->timer->stopTimer();

// Get the elapsed time
$output->writeln("<info>Process finished after {$this->timer->getExecutionTimeInSeconds()} seconds</info>");
```