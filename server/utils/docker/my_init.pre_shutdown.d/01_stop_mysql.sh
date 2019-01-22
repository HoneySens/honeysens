#!/usr/bin/env bash
# Stop mysql in a way so that it 1) properly exits and 2) doesn't restart

sv down mysql
killall -9 mysqld_safe
mysqladmin shutdown