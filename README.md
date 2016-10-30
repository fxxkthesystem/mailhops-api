[![Stories in Ready](https://badge.waffle.io/avantassel/mailhops-api.png?label=ready&title=Ready)](https://waffle.io/avantassel/mailhops-api)
# MailHops API
[www.MailHops.com](http://www.mailhops.com)

<img src="https://www.mailhops.com/images/logos/logo.png" alt="MailHops logo" title="MailHops" align="right" />

MailHops is an email route API. It does a few things:

1. Returns a route an email took based on the Received header IP addresses
1. Returns a map an email took based on the Received header IP addresses
1. Shows the weather of the sender when you provide a [DarkSky](https://darksky.net) API key
1. Performs DNSBL check on messages
1. Displays realtime traffic to the API
1. Post metrics to [Cachethq](https://cachethq.io/) Status page

The route will contain DNSBL lookup results, hostname lookup results, what3words geo locations and the current weather of the senders location.

## Installing

* From Docker: https://hub.docker.com/r/avantassel/mailhops-api
* From Ansible: https://github.com/mailhops/mailhops-api-ansible

## Development
```sh
# Install the MaxMind GeoIP file
./cron_get_geoip.sh

# Get composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Get pear libs
curl -O http://pear.php.net/go-pear.phar
sudo php -d detect_unicode=0 go-pear.phar
sudo pear install Net_DNSBL

#Run npm install
npm install
bower install

# on MaxOSX if you want mongo
brew install mongodb
brew install php70
brew install php70-mongodb

# Import default collections
mongorestore -h [host:port] -d mailhops -u [user] -p [pass] mongo/mailhops/

# Add mongodb.so to your php.ini
echo "extension=mongodb.so" >> /etc/php.ini

cd v1
composer install

cd v2
composer install

# Move and edit the config.json, set mongodb connection string
mv config.sample.json config.json

# on MaxOSX may need to restart apache
sudo apachectl restart

# Start the webserver
php -S 127.0.0.1:8081 -t .
```

Run the setup http://127.0.0.1:8081/v2/setup.php and watch traffic http://127.0.0.1:8081/traffic

If you get permission denied on AWS EC2 you may need to run,

```sh
sudo /usr/sbin/setsebool -P httpd_can_network_connect 1
```

## Testing

```sh
phpunit --bootstrap v1/vendor/autoload.php tests/MailHopsTest --tap
phpunit --bootstrap v2/vendor/autoload.php tests/MailHopsTest --tap
```

## Plugins for Postbox and Thunderbird
- [Postbox & Thunderbird](https://github.com/mailhops/mailhops-plugin)
- [Download](https://addons.mozilla.org/en-US/thunderbird/addon/mailhops/)
