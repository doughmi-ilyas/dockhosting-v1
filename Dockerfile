FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    docker.io \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql mysqli zip

ENV DOCKER_BUILD_CHECK="Build_Attempt_3"

RUN a2enmod rewrite

# Never serve user uploads (they would run as PHP with docker.sock access), secrets, or internals.
RUN printf '%s\n' \
    '<DirectoryMatch "^/var/www/html/(users|vendor|sql|cron|\.git)(/|$)">' \
    '    Require all denied' \
    '</DirectoryMatch>' \
    '<FilesMatch "^\.|^composer\.(json|lock)$">' \
    '    Require all denied' \
    '</FilesMatch>' \
    > /etc/apache2/conf-available/dock-security.conf && a2enconf dock-security

RUN printf '%s\n' \
    'auto_prepend_file=/var/www/html/includes/csrf_guard.php' \
    'session.use_strict_mode=1' \
    'session.cookie_httponly=1' \
    'session.cookie_secure=1' \
    'session.cookie_samesite=Lax' \
    > /usr/local/etc/php/conf.d/dock-security.ini

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p /var/www/html/users/Projects && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 777 /var/www/html/users

RUN echo "ServerName dockhosting.dev" >> /etc/apache2/apache2.conf
