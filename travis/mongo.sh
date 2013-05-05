#!/bin/bash

VERSION=1.3.7

wget http://pecl.php.net/get/mongo-$VERSION.tgz
tar -xzf mongo-$VERSION.tgz
sh -c "cd mongo-$VERSION && phpize && ./configure && sudo make install"
