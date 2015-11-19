#!/bin/bash

VERSION=1.6.11

wget http://pecl.php.net/get/mongo-$VERSION.tgz
tar -xzf mongo-$VERSION.tgz
sh -c "cd mongo-$VERSION && phpize && ./configure && sudo make install"
