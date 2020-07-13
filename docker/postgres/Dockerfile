FROM postgres:11.8

RUN apt-get update \
  && apt-get install -y \
  postgresql-plperl-$PG_MAJOR=$PG_VERSION \
  postgresql-plpython-$PG_MAJOR=$PG_VERSION \
  postgresql-pltcl-$PG_MAJOR=$PG_VERSION \
  && rm -rf /var/lib/apt/lists/*

COPY ./docker/postgres/dbInit/* /docker-entrypoint-initdb.d/
COPY seeddb /docker-entrypoint-initdb.d/01_seeddb.sh
RUN sed -i "s/ seed/ postgres/" /docker-entrypoint-initdb.d/01_seeddb.sh
RUN sed -i "s_sql/_/docker-entrypoint-initdb.d/sql/_" /docker-entrypoint-initdb.d/01_seeddb.sh

COPY sql /docker-entrypoint-initdb.d/sql/