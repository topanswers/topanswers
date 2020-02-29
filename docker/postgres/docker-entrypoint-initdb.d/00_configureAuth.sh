#!/bin/bash
set -e

sed -i 's/host all all all md5/host postgres get samenet trust/' /var/lib/postgresql/data/pg_hba.conf
