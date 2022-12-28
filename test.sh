#!/bin/bash
php generate-hashes.php
rm -rf /workspaces/topanswers/.build/*
cp -a /workspaces/topanswers/get /workspaces/topanswers/.build/
cp -a /workspaces/topanswers/icons /workspaces/topanswers/.build/
cp -a /workspaces/topanswers/lang /workspaces/topanswers/.build/
cp -a /workspaces/topanswers/post /workspaces/topanswers/.build/
cp -a /workspaces/topanswers/*.php /workspaces/topanswers/.build/
cp -a /workspaces/topanswers/*.js /workspaces/topanswers/.build/
ssh admin@3.9.77.187 'sudo mkdir /srv/all/test.new'
tar -c -C /workspaces/topanswers/.build/ -f - . | ssh admin@3.9.77.187 'sudo tar xvf - -C /srv/all/test.new'
ssh admin@3.9.77.187 'sudo mv /srv/all/test /srv/all/test.$(date +"%FT%H%M%S") && sudo mv /srv/all/test.new /srv/all/test'
