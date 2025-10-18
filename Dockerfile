FROM php:8.2-apache

# set directory config

COPY apache.conf /etc/apache2/sites-available/custom.conf

# install needed components

RUN docker-php-ext-install mysqli

# enable apache modules

RUN a2dissite 000-default.conf
RUN a2ensite custom.conf
RUN a2enmod rewrite
RUN service apache2 restart

# basic directory & app setup

COPY --chown=www-data:www-data . /var/www/html

WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]