#!/bin/bash
#php generate-hashes.php
ssh admin@3.9.77.187 'sudo rm -rf /srv/all/prod/*'
tar -c -C /workspaces/topanswers/ -f - . | ssh admin@3.9.77.187 'sudo tar xvf - -C /srv/all/prod'
