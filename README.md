# MailHops API
[www.MailHops.com](http://www.mailhops.com)

<img src="http://www.mailhops.com/images/logos/mailhops395.png" width="200" alt="MailHops logo" title="MailHops" align="right" />

MailHops is an email route API. It does two things:

1. Returns a route an email took based on the Received header IP addresses
2. Returns a map an email took based on the Received header IP addresses

The route will contain DNSBL lookup results, hostname lookup results, what3words geo locations and the current weather of the senders location.

## Install

Get the geoip file, install composer, pear, node, npm and php 5.5 or greater

```sh 
# get the geoip binary file from MaxMind
$ mkdir geoip
$ ./cron_get_geoip.sh

$ curl -sS https://getcomposer.org/installer | php
$ mv composer.phar /usr/local/bin/composer
$ composer install

$ curl -O http://pear.php.net/go-pear.phar
$ sudo php -d detect_unicode=0 go-pear.phar
$ sudo pear install Net_DNSBL

# PHP 5.5 Update (AWS)
$ yum remove php*
$ yum remove httpd.x86_64 httpd-devel.x86_64 httpd-tools.x86_64

$ yum install httpd24.x86_64 httpd24-devel.x86_64 httpd24-tools.x86_64
$ yum install php55.x86_64 php55-common.x86_64 php55-devel.x86_64

$ yum install nodejs npm
```

## Setup the GeoIP cron job

MaxMind updates on the first Tuesday of the month so lets run this at midnight on the first Wednesday

```sh
0 0 *  * 3 /path/to/cron_get_geoip.sh
```

## Setup map dependencies
```sh 
$ npm install -g bower
$ npm install
```

## Options 
options are set in the config.json file

```sh
$ mv config.sample.json config.json
```

### MongoDB
Add connection info in config.json

```sh 
# install the mongo PHP driver
$ pecl install mongo

# add extension=mongo.so to the php.ini
# php5 now stores it in
$ vim /etc/php-5.5.ini

# install default collections
$ mongorestore -h [host:port] -d mailhops -u [user] -p [pass] v1/mongo/mailhops/
```

### what3words
Add API key in config.json

### forecast.io
Add API key in config.json

## If running locally, test it out with
```sh
$ php -S 127.0.0.1:8080 -t .
```

## Plugins
- [Postbox](https://github.com/avantassel/mailhops-postbox)
- [Thunderbird](https://github.com/avantassel/mailhops-thunderbird)