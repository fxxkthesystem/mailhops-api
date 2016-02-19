FROM nginx:latest

# Set Environment variables
# https://mongolab.com
ENV MONGO_HOST='ds011298.mongolab.com'
ENV MONGO_PORT='11298'
ENV MONGO_USER='LuVt78spQ2MtHxUZWjHWuuGCoVD'
ENV MONGO_PASS='dq84WUudevCERJgeuYvjCHzQj3J'
ENV MONGO_DB='mailhops-local'
ENV FORECASTIO_API_KEY=''
ENV W3W_API_KEY=''

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
COPY . /usr/share/nginx

 # Install
WORKDIR /usr/share/nginx
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

# Open port and start nginx
EXPOSE 80
CMD ["/usr/sbin/nginx", "-g", "daemon off;"]
