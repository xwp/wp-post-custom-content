#!/bin/bash

set -e
cd $(dirname $0)

wget https://github.com/ajaxorg/ace-builds/archive/master.tar.gz
tar -xvzf master.tar.gz
rsync -avz --delete ace-builds-master/src-min/ ace/
rm -r master.tar.gz ace-builds-master
