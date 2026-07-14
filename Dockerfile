FROM php:8.4

RUN if [ "$(grep '^VERSION_ID=' /etc/os-release | cut -d '=' -f 2 | tr -d '"')" -eq "9" ]; then \
        sed -i -e 's/deb.debian.org/archive.debian.org/g' \
               -e 's/security.debian.org/archive.debian.org/g' \
               -e '/stretch-updates/d' /etc/apt/sources.list; \
    fi

# Download script to install PHP extensions and dependencies
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions

RUN DEBIAN_FRONTEND=noninteractive apt-get update -q \
    && DEBIAN_FRONTEND=noninteractive apt-get install -qq -y \
      curl \
      dnsutils \
      git \
      iputils-ping \
      less \
      libzip-dev \
      libjpeg62-turbo-dev \
      libpng-dev \
      libfreetype6-dev \
      nano \
      net-tools \
      procps \
      tar \
      tini \
      zip unzip \
# iconv, mbstring and pdo_sqlite are omitted as they are already installed
    && PHP_EXTENSIONS=" \
      amqp \
      bcmath \
      bz2 \
      calendar \
      event \
      exif \
      gd \
      gettext \
      intl \
      ldap \
      memcached \
      mysqli \
      opcache \
      pdo_mysql \
      pdo_pgsql \
      pgsql \
      redis \
      soap \
      sockets \
      xsl \
      zip \
      imagick \
    " \
    && case "$PHP_VERSION" in \
      5.6.*) PHP_EXTENSIONS="$PHP_EXTENSIONS mcrypt mysql";; \
      7.0.*|7.1.*) PHP_EXTENSIONS="$PHP_EXTENSIONS mcrypt";; \
    esac \
    && install-php-extensions $PHP_EXTENSIONS \
    && if command -v a2enmod; then a2enmod rewrite; fi

# Install Composer.
ENV PATH=$PATH:/root/composer/vendor/bin \
  COMPOSER_ALLOW_SUPERUSER=1 \
  COMPOSER_HOME=/root/composer
RUN cd /opt \
  # Download installer and check for its integrity.
  && curl -sSL https://getcomposer.org/installer > composer-setup.php \
  && curl -sSL https://composer.github.io/installer.sha384sum > composer-setup.sha384sum \
  && sha384sum --check composer-setup.sha384sum \
  # Install Composer 2.
  && php composer-setup.php --install-dir=/usr/local/bin --filename=composer --2 \
  # Remove installer files.
  && rm /opt/composer-setup.php /opt/composer-setup.sha384sum

RUN docker-php-source extract
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql zip exif pcntl gd
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN docker-php-ext-install gettext && docker-php-ext-enable gettext
RUN docker-php-ext-install sockets && docker-php-ext-enable sockets

RUN mkdir -p /opt && chmod 777 /opt
WORKDIR /opt
# Pinned to a specific release tag instead of an unpinned clone of master, which is a
# reproducibility/supply-chain risk (the build can break silently when upstream moves on,
# as happened when master started requiring PHP 8.4 while this image was still on 8.3).
RUN git clone --branch 6.0.1 --depth 1 https://github.com/chillerlan/php-qrcode.git \
        && chmod -R 777 ./php-qrcode
RUN cp ./php-qrcode/composer.json /var/www/html/composer.json
RUN mkdir -p /var/www/html/test && chmod 777 /var/www/html/test
RUN cp ./php-qrcode/examples/image.php /var/www/html/test/image.php
RUN cp -R ./php-qrcode/src /var/www/html/

WORKDIR /var/www/html
RUN composer update
RUN composer require phpmailer/phpmailer:^6.9
COPY ./src ./
RUN chmod 755 *;

# Qr code storage lives outside the document root so files can only be reached through
# the authenticated qrcode_image.php / qrcode_zip_download.php endpoints.
RUN mkdir -p /var/www/qrcode-storage/zip && chmod -R 777 /var/www/qrcode-storage

EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80"]
