# ProcessEight CatalogImagesResizeAsync

## Description

Command which re-sizes catalog product images in an asynchronous fashion

## Execution

Re-sizing images found in the Luma theme sample data:

```bash
$ mm processeight:catalog:images:resize:async
Starting timer...
Using 3 child processes

3422 product images resized successfully in 507.51751804352 seconds.

$ mm processeight:catalog:images:resize:async
Starting timer...
Using 6 child processes

3422 product images resized successfully in 201.38359880447 seconds.

$ mm processeight:catalog:images:resize:async
Starting timer...
Using 12 child processes

3422 product images re-sized successfully in 170.98951792717 seconds.

```

## Timer

Using the timer:

```php
<?php

// Inject timer using DI
$this->timer->startTimer();

// Do something
$this->startProcesses($customerIds, $numberOfCildProcesses);

$this->timer->stopTimer();

$output->writeln("<info>Process finished after {$this->timer->getExecutionTimeInSeconds()} seconds</info>");
```