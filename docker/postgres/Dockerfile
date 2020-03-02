FROM postgres

COPY ./docker/postgres/dbInit/* /docker-entrypoint-initdb.d/
COPY ./sql/db.sql /docker-entrypoint-initdb.d/01_db.sql
COPY ./sql/api.sql /docker-entrypoint-initdb.d/02_api.sql

COPY ./sql/* /var/lib/postgresql/git/sql/
COPY ./docker/postgres/seeds/* /seeds/
