#!/bin/bash

echo "deb http://apt.postgresql.org/pub/repos/apt bullseye-pgdg main" > /etc/apt/sources.list.d/pgdg.list
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
apt-get update
export DEBIAN_FRONTEND=noninteractive && apt-get -y install --no-install-recommends postgresql-14 php xxhash

cd
wget https://github.com/aws/aws-sam-cli/releases/latest/download/aws-sam-cli-linux-x86_64.zip
unzip aws-sam-cli-linux-x86_64.zip -d sam-installation
rm aws-sam-cli-linux-x86_64.zip
./sam-installation/install
rm -rf ./sam-installation