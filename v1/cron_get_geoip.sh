#!/bin/sh
#remove all previous downloaded zip files
rm -rf ./*gz

#backup previous dat file
mv GeoLiteCity.dat GeoLiteCity-$(date +%Y%m%d).dat

#download new file
wget http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz

#unzip new file
gunzip GeoLiteCity.dat.gz