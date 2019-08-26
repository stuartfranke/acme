FROM php:7.3.8-apache-buster

RUN apt update \
    && apt install -y \
        bash \
        bash-completion \
        bzip2 \
        ca-certificates \
        curl \
        git \
        gnupg2 \
        dirmngr \
        g++ \
        jq \
        libbz2-dev \
        libedit-dev \
        libfcgi0ldbl \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpq-dev \
        libxml2 \
        libxml2-dev \
        libxslt1.1 \
        libxslt1-dev \
        libzip4 \
        libzip-dev \
        make \
        supervisor \
        unzip \
        vim \
        xml2 \
        zip \
    && rm -r /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    bcmath \
    bz2 \
    calendar \
    exif \
    gd \
    gettext \
    intl \
    mysqli \
    opcache \
    pdo \
    pdo_mysql \
    pcntl \
    soap \
    sockets \
    xmlrpc \
    xsl \
    zip

# Configure Intl and GD
RUN docker-php-ext-configure intl \
    && docker-php-ext-configure gd \
        --with-freetype-dir=/usr/include/freetype2 \
        --with-jpeg-dir=/usr/include/

# Configuring Apache
COPY ./docker/apache/sites-available/phpdev.conf /etc/apache2/sites-available

ENV WEB_ROOT /var/www/html
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data

RUN { \
    echo '<FilesMatch \.php$>'; \
    echo '\tSetHandler application/x-httpd-php'; \
    echo '</FilesMatch>'; \
        echo; \
        echo 'DirectoryIndex disabled'; \
        echo 'DirectoryIndex index.php index.html'; \
        echo; \
        echo "<Directory ${WEB_ROOT}>"; \
        echo '\tOptions -Indexes'; \
        echo '\tAllowOverride All'; \
        echo '</Directory>'; \
    } | tee "$APACHE_CONFDIR/conf-available/phpdev.conf" \
    && a2enconf phpdev \
    && a2enmod rewrite \
    && a2ensite phpdev.conf \
    && a2dissite 000-default.conf

# Install Composer
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /tmp
ENV COMPOSER_VENDOR_DIR ${WEB_ROOT}/vendor
ENV COMPOSER_VERSION 1.8.6

RUN curl --silent --fail --location --retry 3 --output /tmp/installer.php --url https://raw.githubusercontent.com/composer/getcomposer.org/cb19f2aa3aeaa2006c0cd69a7ef011eb31463067/web/installer \
    && php -r " \
        \$signature = '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5'; \
        \$hash = hash('sha384', file_get_contents('/tmp/installer.php')); \
        if (!hash_equals(\$signature, \$hash)) { \
            unlink('/tmp/installer.php'); \
            echo 'Integrity check failed, installer is either corrupt or worse.' . PHP_EOL; \
            exit(1); \
        }" \
    && php /tmp/installer.php --no-ansi --install-dir=/usr/bin --filename=composer --version=${COMPOSER_VERSION} \
    && composer --ansi --version --no-interaction \
    && rm -f /tmp/installer.php \
    && find /tmp -type d -exec chmod -v 1777 {} +

# Install Xdebug
ENV XDEBUG_VERSION 2.7.2
ENV XDEBUG_CONFIG_FILE /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
ENV XDEBUG_REMOTE_LOG_PATH /var/log
ENV XDEBUG_REMOTE_LOG_FILENAME xdebug.log

RUN curl -sSL -o /tmp/xdebug-${XDEBUG_VERSION}.tgz http://xdebug.org/files/xdebug-${XDEBUG_VERSION}.tgz \
    && cd /tmp \
    && tar -xzf xdebug-${XDEBUG_VERSION}.tgz \
    && cd xdebug-${XDEBUG_VERSION} \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && echo "zend_extension=/usr/local/lib/php/extensions/no-debug-non-zts-20180731/xdebug.so" > ${XDEBUG_CONFIG_FILE} \
    && rm -rf /tmp/xdebug*

WORKDIR ${XDEBUG_REMOTE_LOG_PATH}

RUN touch ${XDEBUG_REMOTE_LOG_FILENAME} \
    && chmod 774 ${XDEBUG_REMOTE_LOG_FILENAME} \
    && chown ${APACHE_RUN_USER}:${APACHE_RUN_GROUP} ${XDEBUG_REMOTE_LOG_FILENAME} \
    && { \
        echo 'xdebug.remote_host = host.docker.internal'; \
        echo 'xdebug.default_enable = 1'; \
        echo 'xdebug.remote_enable = 1'; \
        echo 'xdebug.remote_autostart = 1'; \
        echo 'xdebug.remote_connect_back = 0'; \
        echo 'xdebug.remote_log = '${XDEBUG_REMOTE_LOG_PATH}/${XDEBUG_REMOTE_LOG_FILENAME}; \
    } >> ${XDEBUG_CONFIG_FILE}

WORKDIR ${WEB_ROOT}

COPY --chown=${APACHE_RUN_USER}:${APACHE_RUN_GROUP} . ${WEB_ROOT}

EXPOSE 80

HEALTHCHECK --interval=60s --timeout=3s \
  CMD curl -f http://localhost/docker/ping.php || exit 1
