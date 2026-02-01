FROM php:8.2-apache

# Apache mod_rewrite 활성화
RUN a2enmod rewrite

# 작업 디렉토리 설정
WORKDIR /var/www/html

# 소스 복사
COPY src/ /var/www/html/

# Apache 설정 - .htaccess 허용
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>' > /etc/apache2/conf-available/custom.conf \
    && a2enconf custom

# 포트 노출
EXPOSE 80
