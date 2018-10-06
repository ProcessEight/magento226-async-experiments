# Magento 2 Async Experiments

## Experiment One: Async Image Processor

### Description

Intended as a drop-in replacement for core Magento 2 image processing.

### Architecture

#### Phase 1:

* [x] Get count of product images
* [x] Chunk according to number of threads
* [x] Feed each chunk into new sync command
* [x] Create new sync command

#### Phase 2:

A custom Magento module defines an adapter, which passes images to an external microservice app.

The app then queues images and processes them asyncly, passing the results back to Magento.

### To do

* [x] Research how Magento processes images
* [x] Decide on the best point to intercept the images
    * [x] Rewrite the `catalog:images:resize` command to be async
* [x] Decide on which operations (e.g. Resize) should be handled by the app
    * [x] Rather than target individual operations, split the workload of the command into smaller batches and process those batches asyncly
* [x] Create the base module

## Experiment Two: Bulk Database CRUD Operations

### Description

Proof-of-concept implementation of communicating with a MySQL database asyncly to perform regular CRUD operations.

### Architecture

Keep the async solution as simple as possible.

If using https://github.com/friends-of-reactphp/mysql is too complicated, then consider an approach similar to that taken in _Experiment One_.

* [ ] Research how to use https://github.com/friends-of-reactphp/mysql
* [ ] Decide on which CRUD operation to asyncize first
