# api-platform-understanding

Цель проекта:
* разобраться в ApiPlatform с помощью тестов
* запускать данные тесты в своем CI для гарантии, что в обновленной версии ApiPlatform не поломали базовый функционал

## Как запустить тесты или поправить их

Предисловие: в репозитории имеется файл composer.lock.dist, необходимый, чтобы понимать, когда и при каких версиях
зависимостей текущие тесты успешно проходят, но Вы можете запускать их на основании своего composer.lock файла, это
позволит выявлять расхождения в версиях библиотеки ApiPlatform.

Build
```sh
docker build -t yapro/api-platform-understanding:latest -f ./Dockerfile ./
```

Tests
```sh
docker run --rm --user=1000:1000 -v $(pwd):/app yapro/api-platform-understanding:latest bash -c "cd /app \
  && COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader --no-scripts --no-interaction \
  && vendor/bin/phpunit --testsuite=Functional"
```

Dev
```sh
docker run -it --rm --user=1000:1000 -v $(pwd):/app -w /app yapro/api-platform-understanding:latest bash
COMPOSER_MEMORY_LIMIT=-1 composer install -o
```

Debug PHP:
```sh
docker run --rm --user=1000:1000 -v $(pwd):/app yapro/api-platform-understanding:latest bash -c "cd /app \
  && COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader --no-scripts --no-interaction \
  && PHP_IDE_CONFIG=\"serverName=common\" \
     XDEBUG_SESSION=common \
     XDEBUG_MODE=debug \
     XDEBUG_CONFIG=\"max_nesting_level=200 client_port=9003 client_host=172.16.30.130\" \
     vendor/bin/phpunit --cache-result-file=/tmp/phpunit.cache --testsuite=Functional"
```
Если с xdebug что-то не получается, напишите: php -dxdebug.log='/tmp/xdebug.log' и смотрите в лог.

- https://xdebug.org/docs/upgrade_guide
- https://www.jetbrains.com/help/phpstorm/2021.1/debugging-a-php-cli-script.html
