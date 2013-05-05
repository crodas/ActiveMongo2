#!/bin/bash -x 

VERSION=1.3.7

wget http://pecl.php.net/get/mongo-$VERSION.tgz
tar -xzf mongo-$VERSION.tgz
sh -c "cd mongo-1.2.10 && phpize && ./configure && sudo make install"
