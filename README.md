# MailHops

<img src="http://www.mailhops.com/images/logos/mailhops395.png" width="295" alt="MailHops logo" title="MailHops" align="right" />

MailHops is an email route API. It does two things:

1. Returns a route an email took based on the Received header IP addresses
2. Returns a map an email took based on the Received header IP addresses

The route will contain DNSBL lookup results, hostname lookup results and What3Words geo locations. 

## Configuring MailHops API

Get the geoip files, you can also setup a cron job to pull this monthly

```sh
$ ./cron_get_geoip.sh
```

### Install composer and pear

```sh 
$ curl -sS https://getcomposer.org/installer | php
$ mv composer.phar /usr/local/bin/composer
$ composer install

$ curl -O http://pear.php.net/go-pear.phar
$ sudo php -d detect_unicode=0 go-pear.phar
$ sudo pear install Net_DNSBL
```

## Updated the GeoIP file

MaxMind updates on the first Tuesday of the month so lets run this at midnight on the first Wednesday

0 0 *  * 3 /path/to/cron_get_geoip.sh

## Setup map dependencies
```sh 
# this will run gulp
$ npm install
```

## Options

### setup mongo for stats

```sh 
$ pecl install mongo
```

Set user/pass in lib/Connection.php
Set $db_on = true in lib/MailHops.php

### setup w3w API key in the json config file
```sh
$ mv config.sample.json config.json
```

## Test it out
```sh
$ php -S 127.0.0.1:8080 -t .
```
