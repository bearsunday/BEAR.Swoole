# BEAR.Swoole

This library provides the support of Swoole into an BEAR.Sunday application.

##  🚀 Installation

Run the following to install this library:

    composer require bear/swoole
    composer require fastd/http 5.0.x-dev

## 🚀 Entry Script

Place the entry script file at `bin/swoole.php` with IP address and port number.

```php
<?php
require dirname(__DIR__) . '/autoload.php';
exit((require dirname(__DIR__) . '/vendor/bear/swoole/bootstrap.php')(
    'prod-hal-app',       // context
    'MyVendor\MyProject', // application name
    '127.0.0.1',          // IP
    8080                  // port
));
```


## 🚀 Execute

You can run a BEAR.Sunday application with Swoole using the following command:


    php bin/swoole.php

## 🚀 Benchmark

Test with MacBook Air M2 2022

* PHP 8.2

Benchmarking Tool: [wrk](https://github.com/wg/wrk)

**Apache 2.4**

```
wrk http://127.0.0.1/

Running 10s test @ http://127.0.0.1/
  2 threads and 10 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     2.94ms    1.63ms  45.60ms   95.46%
    Req/Sec     1.78k    85.91     1.94k    89.00%
  35369 requests in 10.00s, 6.99MB read
Requests/sec:   3535.29
Transfer/sec:    715.29KB
```

**Swoole 5.0.1**

```
wrk http://127.0.0.1:8080/

Running 10s test @ http://127.0.0.1:8080/
  2 threads and 10 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency    97.58us   26.76us   1.63ms   81.58%
    Req/Sec    49.89k     2.31k   51.06k    95.54%
  1002194 requests in 10.10s, 191.15MB read
Requests/sec:  99230.51
Transfer/sec:     18.93MB
```
