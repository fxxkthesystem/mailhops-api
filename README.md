# MailHops API
[www.MailHops.com](http://www.mailhops.com)

<img src="images/mailhops395.png" width="200" alt="MailHops logo" title="MailHops" align="right" />

MailHops is an email route API. It does two things:

1. Returns a route an email took based on the Received header IP addresses
2. Returns a map an email took based on the Received header IP addresses

The route will contain DNSBL lookup results, hostname lookup results, what3words geo locations and the current weather of the senders location.

## Install With Docker

Install the [Docker Toolbox](https://www.docker.com/products/docker-toolbox)

```sh
docker build .

# copy Image Id
docker images

# create container for php-fpm
docker network create --driver bridge mhnetwork

# run container for php-fpm
docker run -d -p 9000 --net mhnetwork --name php-fpm php:fpm

# run container for mailhops with php-fpm network
docker run --net mhnetwork --name mailhops -p 8080:80 <Image Id>

# get your Docker IP
docker-machine ip default

# SSH into the container
docker exec -t -i mailhops /bin/bash

# clean up docker commands
docker rmi $(docker images -qf "dangling=true")
docker rm $(docker kill $(docker ps -aq))
```

Now open your browser to http://<Docker Ip>:8080

## Install

Get the geoip file, install composer, pear, node, npm and php 5.5 or greater

```sh
# get the geoip binary file from MaxMind
mkdir geoip
./cron_get_geoip.sh

curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
composer install

curl -O http://pear.php.net/go-pear.phar
sudo php -d detect_unicode=0 go-pear.phar
sudo pear install Net_DNSBL

# PHP 5.5 Update (AWS)
yum remove php*
yum remove httpd.x86_64 httpd-devel.x86_64 httpd-tools.x86_64

# install web server
yum install nginx
# OR
yum install httpd24.x86_64 httpd24-devel.x86_64 httpd24-tools.x86_64

cat nginx.conf >> /etc/nginx/conf.d/mailhops.conf

yum install php55.x86_64 php55-common.x86_64 php55-devel.x86_64 php55-fpm
yum install nodejs npm --enablerepo=epel

# make sure httpd starts on boot
chkconfig httpd on
OR
chkconfig nginx on
chkconfig php-fpm-5.5 on

/etc/init.d/httpd start
# OR
/etc/init.d/nginx start
/etc/init.d/php-fpm-5.5 start

```

## Setup the GeoIP cron job

MaxMind updates on the first Tuesday of the month so lets run this at midnight on the first Wednesday

```sh
0 0 *  * 3 /path/to/cron_get_geoip.sh
```

## Setup map dependencies
```sh
npm install -g bower
npm install
```

## Options
options are set in the config.json file

```sh
mv config.sample.json config.json
```

### MongoDB
Add connection info in config.json

```sh
# install the mongo PHP driver
pecl install mongo

# add extension=mongo.so to the php.ini
# php5 now stores it in
vim /etc/php-5.5.ini

# install default collections
mongorestore -h [host:port] -d mailhops -u [user] -p [pass] v1/mongo/mailhops/
```

### what3words
Add API key in config.json

### forecast.io
Add API key in config.json

## If running locally, test it out with
You may want to use OpenDNS if running locally, there are issues with Google DNS servers.

```sh
php -S 127.0.0.1:8080 -t .
```

## Plugins for Postbox and Thunderbird
- [Postbox & Thunderbird](https://github.com/avantassel/mailhops-plugin)
- [Download](https://addons.mozilla.org/en-US/thunderbird/addon/mailhops/)
