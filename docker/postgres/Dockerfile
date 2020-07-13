FROM postgres:11.8-alpine

COPY ./docker/postgres/dbInit/* /docker-entrypoint-initdb.d/
COPY seeddb /docker-entrypoint-initdb.d/01_seeddb.sh
RUN sed -i "s/ seed/ postgres/" /docker-entrypoint-initdb.d/01_seeddb.sh

COPY sql /docker-entrypoint-initdb.d/sql/