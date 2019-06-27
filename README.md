# LTO Workflow engine

Workflow service for running Live Contract processes.

## Requirements

- [PHP](http://www.php.net) >= 7.2.0
- [MongoDB](http://www.mongodb.org/) >= 3.2
- [Git](http://git-scm.com)

_Required PHP extensions are marked by composer_


## Installation

The LTO full node contains the workflow engine. See how to [setup a node](https://github.com/legalthings/lto).

Alternatively; clone from GitHub for development

```
git clone git@github.com:legalthings/workflow-engine.git
cd workflow-engine
composer install
bin/codecept build
```

## Tests

Test use the [Codeception test framework](https://codeception.com/). The project contains unit and api tests. Code in
the controllers is only covered by the api tests.

### Run tests

    bin/codecept run

To run only a single test use

    bin/codecept run api Default/100-InfoCept

For more options see https://codeception.com/docs/reference/Commands#run

### HTTP Mock

External services MUST be mocked. For api tests use `$I->expectHttpRequest()` to mock and assert external http calls
done by Guzzle.

```php
$I->expectHttpRequest(function (Request $request) use ($I) {
    $I->assertEquals('http://example.com', (string)$request->getUri());
    $I->assertEquals('application/json', $request->getHeaderLine('Content-Type'));

    $I->assertJsonStringEqualsJsonString('{"foo": "bar"}', (string)$request->getBody());
    
    return new Response(200);
});
```

## Serve

To serve the project on localhost run

```
php -S localhost:4001 app.php
```

_Note, it's preferable to work TDD and use tests when developing. This means you would hardly ever need to run this
service locally._


## JSON REST API

```
POST   /scenarios/                Create a new scenario
GET    /scenarios/{id}            Get a specific scenario
POST   /scenarios/{id}/meta       Update the meta information of the scenario

POST   /processes/                Create a process
GET    /processes/{id}            Get a specific process
POST   /processes/{id}/invoke     Invoke a system action if possible
POST   /processes/{id}/response   Submit a response for a running process
POST   /processes/{id}/meta       Update the meta information of the process
DELETE /processes/{id}            Remove a process

POST   /identities/               Register an identity (overwrites)
GET    /identities/{id}           Get a specific identity
DELETE /identities/{id}           Unregister the identity
```
