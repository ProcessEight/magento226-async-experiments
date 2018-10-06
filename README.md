# Magento 2 Async Experiments

## Experiment One: Async Image Processor

### Description

Intended as a drop-in replacement for core Magento 2 image processing

### Architecture

#### Phase 1:

Get count of product images
Chunk according to number of threads
Feed each chunk into new sync command
Create new sync command

#### Phase 2:

A custom Magento module defines an adapter, which passes images to an external microservice app.

The app then queues images and processes them asyncly, passing the results back to Magento.

### To do

* [x] Research how Magento processes images
* [x] Decide on the best point to intercept the images
    * [ ] Rewrite the `catalog:images:resize` command to be async
        * [ ] Use https://github.com/clue/reactphp-block to create the event loop
        * [ ] Use https://github.com/reactphp/filesystem to interact (load, save) with the filesystem
* [x] Decide on which operations (e.g. Resize) should be handled by the app
* [x] Create the base module
* [ ] Create the adapter

## Experiment Two: Bulk Database CRUD Operations

### Description

Proof-of-concept implementation of communicating with a MySQL database asyncly to perform regular CRUD operations.

### Architecture

* [ ] Decide on which CRUD operation to asyncize first
