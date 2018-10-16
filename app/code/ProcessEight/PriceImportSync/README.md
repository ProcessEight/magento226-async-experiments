# ProcessEight PriceImportSync

## Description

Command which updates catalog product prices in a synchronous fashion

## Execution

```bash
$ mr2 processeight:catalog:prices:import:sync
Starting timer...
2046/0 [-->-------------------------]   0% 1 sec 54.0 MiB 	| WSH12
Stopped timer.
2045 prices imported successfully in 0.4961941242218 seconds.
```

## Timer

```php
<?php

// Inject timer using DI
$this->timer->startTimer();

// Do something
$this->startProcesses($customerIds, $numberOfChildProcesses);

$this->timer->stopTimer();

$output->writeln("<info>Process finished after {$this->timer->getExecutionTimeInSeconds()} seconds</info>");
```