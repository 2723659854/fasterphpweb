#!/bin/sh sh
cd /usr/src/myapp/fasterphpweb
git fetch && git pull origin master
docker exec "my-php" php /usr/src/myapp/fasterphpweb/start.php restart -d