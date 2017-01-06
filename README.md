# SimpleBannerRotator
SimpleBannerRotator &mdash; это предельно минимизированный и простой ротатор баннеров с персональными аккаунтами для рекламодателей.
Весь ротатор состоит из трёх основных частей:
* Зоны
* Рекламодатели
* Баннеры

## Требования
* PHP >= 5.5
* [Phalcon](http://phalconphp.com/) >= 2.0.0

## Установка
1. Переименовать файл /app/config/config.ini.example в config.ini и отредактировать его, сменив данные для поключения к БД
2. Импортировать sql_dump.sql в базу

Конфигурационный файл для nginx прилагается ([banners.conf](https://github.com/braska/SimpleBannerRotator/blob/master/banners.conf))
Стандартные данные для входа в админпанель:
E-mail: test@test.ru
Пароль: 4444

## Запуск в Docker
- `docker-compose build` - сборка образа
- `docker-copmose up` - запуск версии для разработки
- `docker-compose -f docker-compose.prod.yml up` - запуск production версии

## Использование
1. В шапку сайта, на котором требуется транслировать баннеры, нужно вставить основной скрипт баннеро-ротатора:

```html
<script src="//mybannerratatoraddress.ru/rotator/get_js" type="text/javascript"></script>
```
2. Создать зону трансляции баннеров в разделе "Зоны".
3. Скопировать и вставить сгенерированный код зоны на сайт.
4. Добавить баннеры
