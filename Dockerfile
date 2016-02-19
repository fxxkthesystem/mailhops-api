FROM nginx:latest

# Set Environment variables
# https://mongolab.com
# ENV MONGO_HOST=''
# ENV MONGO_PORT=''
# ENV MONGO_USER=''
# ENV MONGO_PASS=''
# ENV MONGO_DB=''
# ENV FORECASTIO_API_KEY=''
# ENV W3W_API_KEY=''

MAINTAINER Andrew Van Tassel <andrew@andrewvantassel.com>

RUN apt-get update && apt-get install -y curl \
                                         nodejs \
                                         npm \
                                         git \
                                         build-essential \
                                         php5-cli \
                                         php5-fpm \
                                         php5-mongo \
                                         php5-curl \
                                         php-pear

RUN ln -s /usr/bin/nodejs /usr/bin/node

# Copy files
COPY config.sample.json config.json
COPY . /var/www/mailhops-api/

 # Install
WORKDIR /var/www/mailhops-api
RUN npm install -g npm bower

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install

# npm and bower
RUN npm install
RUN bower --allow-root install -g
RUN pear install Net_DNSBL

ADD ./nginx-docker.conf /etc/nginx/conf.d/default.conf

# Get GeoIP file
RUN mkdir geoip
RUN ./cron_get_geoip.sh

# Add cronjob
RUN crontab -l | { cat; echo "0 0 *  * 3 /var/www/mailhops-api/cron_get_geoip.sh"; } | crontab -

# Open port and start nginx
EXPOSE 80
CMD ["/usr/sbin/nginx", "-g", "daemon off;"]
