# Examples

Here are some [`docker-compose`] examples.

## `docker-compose.simple.yml`

This is the most basic setup of `snappymail`, using [SQLite](https://www.sqlite.org/index.html) as the database.

Start the stack:

```sh
docker-compose -f docker-compose.simple.yml up
```

Get the Admin Panel password:

```sh
docker exec -it $( docker-compose -f docker-compose.simple.yml ps -q snappymail ) cat /snappymail/data/_data_/_default_/admin_password.txt
```

Now, login to [https://localhost:8888/?admin](https://localhost:8888/?admin) with user `admin` and the admin password.

## `docker-compose.mysql.yml`

This runs `snappymail`, using [MariaDB](https://mariadb.org/) (a fork of [MYSQL](https://www.mysql.com/)) as the database.

Start `snappymail` and `mysql`:

```sh
docker-compose -f docker-compose.mysql.yml up
```

Get the Admin Panel password:

```sh
docker exec -it $( docker-compose -f docker-compose.mysql.yml ps -q snappymail ) cat /snappymail/data/_data_/_default_/admin_password.txt
```

Now, login to [https://localhost:8888/?admin](https://localhost:8888/?admin) with user `admin` and the admin password.

To setup MySQL as the DB, in `Admin panel`, click `Contacts`, check `Enable contacts` and , and under `Storage (PDO)` choose the following:

- Type: `MySQL`
- Data Source Name (DSN): `host=mysql;port=3306;dbname=snappymail`
- User `snappymail`
- Password `snappymail`

Click the `Test` button. If it turns green, MySQL is ready to be used for contacts.

Redis caching is now enabled.

## `docker-compose.postgres.yml`

This runs `snappymail`, using [PostgreSQL](https://hub.docker.com/_/postgres) as the database.

Start `snappymail` and `postgres`:

```sh
docker-compose -f docker-compose.postgres.yml up
```

Get the Admin Panel password:

```sh
docker exec -it $( docker-compose -f docker-compose.postgres.yml ps -q snappymail ) cat /snappymail/data/_data_/_default_/admin_password.txt
```

Now, login to [https://localhost:8888/?admin](https://localhost:8888/?admin) with user `admin` and the admin password.

To use PostgreSQL as the DB, in `Admin panel`, click `Contacts`, check `Enable contacts` and , and under `Storage (PDO)` choose the following:

- Type: `PostgresSQL`
- Data Source Name (DSN): `host=postgres;port=5432;dbname=snappymail`
- User `snappymail`
- Password `snappymail`

Click the `Test` button. If it turns green, PostgreSQL is ready to be used for contacts.

To setup Redis for caching, in `Admin panel`, click `Config`, under `labs`, update the following configuration options:

- `cache > enable`: yes
- `cache > fast_cache_driver`: `redis`
- `labs > fast_cache_redis_host`: `redis`
- `labs > fast_cache_redis_port`: `6379`

Redis caching is now enabled.
