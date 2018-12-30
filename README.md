# Plasma Driver MySQL/MariaDB [![Build Status](https://travis-ci.org/PlasmaPHP/driver-mysql.svg?branch=master)](https://travis-ci.org/PlasmaPHP/driver-mysql) [![Build Status](https://scrutinizer-ci.com/g/PlasmaPHP/driver-mysql/badges/build.png?b=master)](https://scrutinizer-ci.com/g/PlasmaPHP/driver-mysql/build-status/master) [![Code Coverage](https://scrutinizer-ci.com/g/PlasmaPHP/driver-mysql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/PlasmaPHP/driver-mysql/?branch=master)

Plasma provides an asynchronous, non-blocking (data access) Database Abstraction Layer. This is the MySQL/MariaDB driver for Plasma.

The driver uses ReactPHP to asychronously interface with the database server.

The driver supports setting a connection charset through the driver options, it should be noted that the default is `utf8mb4` (UTF-8), which is a character set you should use all the way through.

It should also be noted, that MySQL versions, which support the `FOUND_ROWS` flag, can return a non-zero value when using `StreamQueryResultInterface::getAffectedRows()` for `SELECT` queries.

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
$client = \Plasma\Client::create($factory, 'user:password@localhost:3306/database', array());

// Code which uses the client to run queries against the database

$loop->run();
```

Unix socket connections are supported using the `unix://` scheme, so the example connect uri would look like this:
```
unix://user:password@localhost/database
```

# Type Extensions
This driver uses a type extensions manager registered under the name `driver-mysql`.
When decoding rows received from the database, the type extensions can get two different type of values to decode, depending on the used protocol.

When using the text protocol (regular queries), then the type extensions get the raw value as string.

However when using the binary protocol (prepared statements), then the type extensions get the used `\Plasma\BinaryBuffer` instance.
It must be used with care. Reading too much from it can lead into dropped row, because the remaining fields can not be properly decoded.

# Documentation
https://plasmaphp.github.io/driver-mysql/
