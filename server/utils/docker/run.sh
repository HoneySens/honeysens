#!/usr/bin/env bash
set -e

# Execute startup scripts in alphabetical order
find /etc/startup.d/* -type f -executable -exec {} \;
