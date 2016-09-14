#!/bin/sh
cd "$(dirname "$0")"

if [ ! -d "geoip" ]; then
	mkdir geoip
fi

#remove all previous downloaded zip files
rm -rf geoip/*gz geoip/*mmdb

# ------------- IPV4 && IPV6 -------------
#download file
wget http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz -O geoip/GeoLite2-City.mmdb.gz

#unzip file
if [ -f "geoip/GeoLite2-City.mmdb.gz" ]; then
	gunzip geoip/GeoLite2-City.mmdb.gz
fi
