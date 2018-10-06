# ProcessEight CatalogImagesResizeSync

## Description

Command which resizes catalog product images in a synchronous fashion

## Execution

```bash
$ mr2 processeight:catalog:images:resize:sync
Starting timer...
 700/3422 [=====>----------------------]  20% 12 mins 36.0 MiB 	| /w/s/wsh07-blue_main.jpg
3422 product images resized successfully in 767.14983797073 seconds.
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