# Change Log
All notable changes to this project will be documented in this file.

## [2.0.1] - 2017-3-24

### Fixed
- DNSBL icon showing if not listed
- Removed modal popup
- link to whois

## [2.0.0] - 2016-10-12

### Changed

- Moved composer to v1 and v2 and must be run in each during install
- Updated GuzzleHttp to 6.2.1
- Changed Guzzle Cache to work with Guzzle 6.2.1
- Upgraded to PHP7
- Updated MongoDB driver
- Updated ForecastIO to [DarkSky](https://darksky.net)
- Updated HTTP response codes

### Added

- Support for MongoDB replicsets
- Realtime traffic map with US and world view
- Whois + Google fallback to get city/state, lat/lng if IP is not found in MaxMind
- [Cachet](cachethq.io) status page support
- [PHPUnit](https://phpunit.de/) tests

## [1.2.3] - 2015-11-13
### Fixed

- Check for DNSBL function, issue with dev environment
- Check that IP address starts with digit less than 240 (IANA-RESERVED)
- Check for lat/lng in route for weather lookup

## [1.2.1] - 2015-09-18
### Changed
- Added time zone parse for city if no city in MaxMind response

## [1.2.0] - 2015-05-31
### Changed
- Update GeoIP to use new MaxMind GeoLite2-City
- Update map provider to Leaflet
- Update map style
- Update logo
- Update PHP5.5

### Added
- IPV6 support
- Language support for 'de','en','es','fr','ja','pt-BR','ru','zh-CN'
- Forecast.IO to get the senders weather
- What3Words, see README for adding and API key
- Composer for PHP libraries
- node, npm, bower for new map styles
- .htaccess file

### Fixed
- Fix map panning
