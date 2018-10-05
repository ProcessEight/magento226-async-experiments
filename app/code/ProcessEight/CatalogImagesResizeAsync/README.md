# ProcessEight CatalogImagesResizeAsync

## Description

Command which re-sizes catalog product images in an asynchronous fashion

## Execution

```bash
$ mm processeight:catalog:images:resize:async
Starting timer...

3422 product images resized successfully in 507.51751804352 seconds.
```

## Timer

Using the timer:

```php
<?php

// Inject timer using DI
$this->timer->startTimer();

// Do something
$this->startProcesses($customerIds, $numberOfThreads);

$this->timer->stopTimer();

$output->writeln("<info>Process finished after {$this->timer->getExecutionTimeInSeconds()} seconds</info>");
```