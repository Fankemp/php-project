FROM php:8.3-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client

# Очищаем кэш apt, чтобы уменьшить размер образа
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Устанавливаем расширения PHP, необходимые для Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Задаем рабочую директорию
WORKDIR /var/www/html

# Создаем системного пользователя, чтобы избежать проблем с правами на файлы
# (опционально для локальной разработки, но это хороший тон)
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www
USER www