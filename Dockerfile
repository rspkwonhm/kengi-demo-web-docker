FROM php:8.2-apache

# 必要なパッケージをインストール
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Apache mod_rewrite 활성화
RUN a2enmod rewrite

# Composer インストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 작업 디렉토리 설정
WORKDIR /var/www/html

# 소스 전체 복사 (composer.json 포함)
COPY src/ /var/www/html/

# Composer 依存関係インストール (vendor フォルダ生成)
RUN composer install --no-dev --optimize-autoloader

# Apache 설정 - .htaccess 허용
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>' > /etc/apache2/conf-available/custom.conf \
    && a2enconf custom

# 포트 노출
EXPOSE 80
