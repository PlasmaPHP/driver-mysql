# Plasma Driver MySQL/MariaDB

Plasma aims to be an asynchronous, non-blocking (data access) Database Abstraction Layer. This is the MySQL/MariaDB driver for Plasma.

The driver supports setting a connection charset through the query string `charset=MY_CHARSET`. It's recommended to add `?charset=utf8mb4` to the connect uri. A connection collate can also be set through the same way as charset.
