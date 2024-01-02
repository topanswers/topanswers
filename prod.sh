#!/bin/bash
rm -rf /workspaces/topanswers/.build/*
cp -a /workspaces/topanswers/get /workspaces/topanswers/.build/
cp -a /workspaces/topanswers/icons /workspaces/topanswers/.build/
cp -a /workspaces/topanswers/lang /workspaces/topanswers/.build/
cp -a /workspaces/topanswers/post /workspaces/topanswers/.build/
cp -a /workspaces/topanswers/*.php /workspaces/topanswers/.build/
cp -a /workspaces/topanswers/*.js /workspaces/topanswers/.build/
find /workspaces/topanswers/.build -type f -iname "*.php" -exec sed -i "s/password=password/password=$PGAPIPASSWORD/g" "{}" +;
chmod -R 777 .build
ssh admin@3.9.77.187 'sudo mkdir /srv/all/prod.new'
tar -c -C /workspaces/topanswers/.build/ -f - . | ssh admin@3.9.77.187 'sudo tar xvf - -C /srv/all/prod.new'
ssh admin@3.9.77.187 'sudo mv /srv/all/prod /srv/all/prod.$(date +"%FT%H%M%S") && sudo mv /srv/all/prod.new /srv/all/prod'
