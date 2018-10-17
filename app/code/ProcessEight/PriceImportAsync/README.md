# ProcessEight PriceImportAsync

## Description

Command which imports catalog product base prices in an asynchronous fashion

## Execution

Updating 2048 products from the sample data with new base prices:

```bash
# Base prices only
$ mr2 processeight:catalog:prices:import:async 3
Starting timer...
Using 3 child processes
2 prices imported successfully.
681 prices imported successfully.
681 prices imported successfully.
681 prices imported successfully.

Stopped timer.
All product prices imported successfully in 0.51632714271545 seconds.

$ mr2 processeight:catalog:prices:import:async 6
Starting timer...
Using 6 child processes
340 prices imported successfully.
5 prices imported successfully.
340 prices imported successfully.
340 prices imported successfully.
340 prices imported successfully.
340 prices imported successfully.
340 prices imported successfully.

Stopped timer.
All product prices imported successfully in 0.66675209999084 seconds.

$ mr2 processeight:catalog:prices:import:async 12
Starting timer...
Using 12 child processes
170 prices imported successfully.
170 prices imported successfully.
5 prices imported successfully.
170 prices imported successfully.
170 prices imported successfully.
170 prices imported successfully.
170 prices imported successfully.
170 prices imported successfully.
170 prices imported successfully.
170 prices imported successfully.
170 prices imported successfully.
170 prices imported successfully.
170 prices imported successfully.

Stopped timer.
All product prices imported successfully in 1.0194411277771 seconds.

# Base prices and Cost prices
$ mr2 processeight:catalog:prices:import:async 
Starting timer...
Using 3 child processes
682 prices imported successfully.
682 prices imported successfully.
682 prices imported successfully.

Stopped timer.
All product prices imported successfully in 0.51760196685791 seconds.
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