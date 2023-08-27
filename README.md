# cifra-test

## Задача

### Микросервис баланса пользователей

Приложение хранит в себе идентификаторы пользователей и их баланс. 

Взаимодействие с ним осуществляется исключительно с помощью брокера очередей.
По требованию внешней системы (общение между микросервисами через очередь
сообщений), микросервис может выполнить одну из следующих операций со счетом
пользователя:
- Списание 
- Зачисление
- (будет плюсом, но не обязательно) Блокирование с последующим списанием или
разблокированием. Заблокированные средства недоступны для использования. Блокировка
означает что некая операция находится на авторизации и ждет какого-то внешнего
подтверждения, ее можно впоследствии подтвердить или отклонить
- Перевод от пользователя к пользователю 
- После проведения любой из этих операций генерируется событие.

Основные требования к воркерам:
- Код воркеров должен безопасно выполняться параллельно в разных процессах;
- Воркеры могут запускаться одновременно в любом числе экземпляров и выполняться
произвольное время;
- Все операции должны обрабатываться корректно, без двойных списаний.

Будет плюсом покрытие кода юнит-тестами.

В пояснительной записке к выполненному заданию необходимо указать перечень
используемых инструментов и технологий, способ развертки приложения.

Требования к окружению:
- Язык программирования: PHP &gt;= 8, стандарт кодирования - PSR-12
- Можно использовать: любые фреймворки, реляционные БД для хранения баланса,
брокеры очередей, key-value хранилища.

## Решение

Реализовал на 
- PHP 8.1
- Laravel 10.2
- PostgreSQL - основное хранилище
- RabbitMQ - входные данные
- Redis - локи

### Для тестирования:
* запустить
    * `composer install` 
    * `cp .env.example .env`
    * `./vendor/bin/sail up -d`
    * `./vendor/bin/sail php artisan migrate`
    * `./vendor/bin/sail php artisan app:test-command` - добавление тестовых сообщений в очередь
    * `./vendor/bin/sail php artisan rabbitmq:consume --queue='incoming_transactions'` - обработка сообщений из очереди

### Детали релизации:

#### Входящие сообщения

Формат сообщения
```
{
    "id": string, // uuid
    "user_id": int, // кому перевод
    "sender_user_id": int, // от кого перевод (не обязательно)
    "amount": float,
    "operation": string // hold|complete|cancel
}
```

#### Принятые решения не зафиксированные в задаче

- пользователи могут уходить в минус бесконечно
- при холде деньги списываются у отправителя, но не попадают к получателю
  - при подтверждении, добавляются получателю
  - при отмене, возвращаются отправителю


