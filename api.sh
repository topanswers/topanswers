#!/bin/bash
psql "host=18.169.61.181 dbname=ta user=postgres sslmode=require" -f sql/api.sql