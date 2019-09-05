#!/usr/bin/env bash
set -e

# If there are no traces of an already existing CA, create a new one
if [[ (! -f /srv/ca/ca.key) && (-x /srv/ca/create.sh) ]]; then
    (cd /srv/ca && ./create.sh)
fi