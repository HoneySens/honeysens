#!/usr/bin/env bash
set -e

# Execute startup scripts in alphabetical order
for s in /etc/startup.d/*; do $s; done