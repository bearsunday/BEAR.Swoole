# BEAR.Swoole

This library provides the support of Swoole into an BEAR.Sunday application.

##  🚀 Installation

Run the following to install this library:

    composer require bear/swoole


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

Test with Intel Core i5 3.8 GHz iMac (Retina 5K, 27-inch, 2017)

* PHP 7.2
 * 24G Memory

Benchmarking Tool: [wrk](https://github.com/wg/wrk)

**Apache 2.4**

```
wrk -t4 -c10 -d10s http://127.0.0.1/

Running 10s test @ http://127.0.0.1/
  4 threads and 10 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency    17.53ms   35.39ms 252.04ms   87.12%
    Req/Sec   741.81    351.80     1.57k    65.49%
  29500 requests in 10.04s, 8.45MB read
Requests/sec:   2937.46
Transfer/sec:    861.12KB
```

**Swoole 4.2.7**

```
wrk -t4 -c10 -d10s http://127.0.0.1:8080/

Running 10s test @ http://127.0.0.1:8080/
  4 threads and 10 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   468.61us    0.99ms  31.52ms   98.25%
    Req/Sec     5.26k     1.25k    8.00k    63.37%
  211588 requests in 10.10s, 40.36MB read
Requests/sec:  20946.90
Transfer/sec:      4.00MB
```
