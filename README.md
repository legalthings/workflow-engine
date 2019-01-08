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

Test use the [Codeception test framework](https://codeception.com/). The project contains unit and api tests. Code in the
controllers is only covered by the api tests.

### Run tests

    bin/codecept run

To run only a single test use

    bin/codecept run api Default/100-InfoCept

For more options see https://codeception.com/docs/reference/Commands#run

### HTTP Mock

External services MUST be mocked. For api tests use `$I->expectHttpRequest()` to mock and assert external http calls done by Guzzle.

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
php -S localhost:4000 -t www
```

_Note, it's preferable to work TDD and use tests when developing. This means you would hardly ever need to run this service
localy._


## JSON REST API

```
GET  /scenarios/             List all scenarios (filterable)
POST /scenarios/             Create a new scenario
GET  /scenarios/{id}         Get a specific scenario
POST /scenarios/{id}/meta    Update the meta information of the scenario

GET  /processes/             List all processes (filterable)
POST /processes/             Create a process (explictly)
POST /processes/{id}/done    Invoke a system action if possible

POST /responses/             Submit a response for a running or new process
```

See the [Live Contract specification](https://docs.livecontracts.io/) for the JSON format or scenarios and processes.

### Filter

You may use the HTTP query to filter on any field

    GET /processes/?state=running

Use the dot (`.`) notation to filter on properties of child objects.

    GET /processes/?default_action.actor.id=8b236475-c83b-4437-ad1c-4f283f935eb8

Filter keys may include an operator. The following operator are supported by default

Key            | Value  | Description
-------------- | ------ | ---------------------------------------------------
"field"        | scalar | Field is the value
"field (not)"  | scalar | Field is not the value
"field (min)"  | scalar | Field is equal to or greater than the value
"field (max)"  | scalar | Field is equal to or less than the value
"field (any)"  | array  | Field is one of the values in the array
"field (none)" | array  | Field is none of the values in the array

If the field is an array, you may use the following operators

Key            | Value  | Description
-------------- | ------ | ---------------------------------------------------
"field"        | scalar | The value is part of the field
"field (not)"  | scalar | The value is not part of the field
"field (any)"  | array  | Any of the values are part of the field
"field (all)"  | array  | All of the values are part of the field
"field (none)" | array  | None of the values are part of the field

To filter between two values, use both `(min)` and `(max)`.

