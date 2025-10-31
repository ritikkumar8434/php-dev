FROM php:8.1-apache
RUN docker-php-ext-install pdo pdo_mysql
COPY app/ /var/www/html/
WORKDIR /var/www/html
# Environment variables for RDS
ENV DB_HOST=database-1.c5kss0q0uaa8.ap-south-1.rds.amazonaws.com
ENV DB_NAME=phpapp
ENV DB_USER=admin
ENV DB_PASS=admin1234
EXPOSE 80

