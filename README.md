## Overview
This is a Joomla 6‑based web application serving as a prototype of the interface and logic of consistent CRM stages.
=====================

## Technology Stack
[joomla:6.0](https://manual.joomla.org)
[docker](https://www.docker.com/)
[PHP: 8.3.*](https://www.php.net/releases/8.3/en.php)
[Database](https://mysql.com): MySQL 8.4


Installation
## Clone the repository:
```
git clone https://github.com/sevarostov/joomla
```
## Install dependencies:
```
composer install
```
## Configure environment:
```
cp .env.example .env
```
# Edit .env with your credentials

## Run database migrations:
```
php bin/console doctrine:migrations:migrate
```

## Docker build:
```
   docker build -t php:latest --file ./docker/php/Dockerfile --target php ./docker/php
```
## Docker compose:
```
docker compose -f docker-compose.local.yml up --build -d 
docker compose -f docker-compose.local.yml up -d
docker compose -f docker-compose.local.yml down
```
