# Plasma Driver MySQL/MariaDB [![Build Status](https://travis-ci.org/PlasmaPHP/driver-mysql.svg?branch=master)](https://travis-ci.org/PlasmaPHP/driver-mysql) [![Build Status](https://scrutinizer-ci.com/g/PlasmaPHP/driver-mysql/badges/build.png?b=master)](https://scrutinizer-ci.com/g/PlasmaPHP/driver-mysql/build-status/master) [![Code Coverage](https://scrutinizer-ci.com/g/PlasmaPHP/driver-mysql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/PlasmaPHP/driver-mysql/?branch=master)

Plasma aims to be an asynchronous, non-blocking (data access) Database Abstraction Layer. This is the MySQL/MariaDB driver for Plasma.

The driver uses ReactPHP to asychronously interface with the database server.

The driver supports setting a connection charset through the query string `charset=MY_CHARSET`. It's recommended to add `?charset=utf8mb4` to the connect uri. A connection collate can also be set through the same way as charset.

# Getting Started
You can install this component by using `composer`. The command is

```
composer require plasma/driver-mysql
```

After you've used composer to install the components and the dependencies, you get started by creating an instance of the factory.

The factory takes a loop instance and an array of options (see the factory class documentation for the available options).

The factory also gives you the ability to asychronously interface with the filesystem using `react/filesystem`, if a `LOCAL INFILE` request ever occurres.

Additionally you can create your own auth plugins, if your database server uses an authentication plugin this driver doesn't support (yet).

```php
$loop = \React\EventLoop\Factory::create();

$factory = \Plasma\Drivers\MySQL\DriverFactory::create($loop, array());
$client = \Plasma\Client::create($factory, 'user:password@localhost:3306/database?charset=utf8mb4', array());

// Code which uses the client to run queries against the database

$loop->run();
```

# Documentation
Soon.
