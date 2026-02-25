#!/bin/sh 

php bin/console d:d:d --force
php bin/console d:d:c
php bin/console d:s:u --force --complete
php bin/console d:f:l  --env=dev