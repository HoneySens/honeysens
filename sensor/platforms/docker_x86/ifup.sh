#!/bin/sh
args=`echo $@ | sed -e 's/--no-act/-n/g'`
/usr/local/bin/ifup $args