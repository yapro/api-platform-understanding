#!/bin/bash

# Данный скрипт сравнивает 2 версии файлов (оба файла сгенерированы библиотекой api-platform):
# 1-ый файл сгенерирован ранее и находится в системе контроля версий
# 2-ой файл генерируется текущим скриптом и не должен сильно отличаться от 1-ого файла
# Скрипт создан чтобы контролировать изменения выполняемые api-platform (например при обновлении версии api-platform)
# или разработчиком, который случайно через аннотацию/(изменения в коде) меняет обратную совместимось

PATH_TO_REPOSITORY=$(readlink -f "$(dirname "$(dirname "$0")")")

LOCAL_OPENAPI_FILE_PATH=$PATH_TO_REPOSITORY/public/oas/api-platform.yaml

if [ ! -f $LOCAL_OPENAPI_FILE_PATH ]; then
  echo "$LOCAL_OPENAPI_FILE_PATH NOT FIND."
  exit 1;
fi

API_PLATFORM_GENERATED_OPENAPI_FILE_PATH=/tmp/api-platform.yaml

$PATH_TO_REPOSITORY/bin/console api:openapi:export --yaml --output=$API_PLATFORM_GENERATED_OPENAPI_FILE_PATH

DIFF_FILES=`diff "$LOCAL_OPENAPI_FILE_PATH" "$API_PLATFORM_GENERATED_OPENAPI_FILE_PATH"`
if [ "$DIFF_FILES" != "" ]; then
  echo "$DIFF_FILES";
  exit 1;
fi
