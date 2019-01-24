#!/usr/bin/env bash
# Controls the LED status color on the HoneySens LED board.

if [ ! $# -eq 1 ]; then
  echo "Usage: led.sh <red|green|orange|off>"
  exit 1
fi

if [ ! -d /sys/class/gpio/gpio26 ]; then
  echo 26 > /sys/class/gpio/export
  sleep 1
fi
if [ ! -d /sys/class/gpio/gpio60 ]; then
  echo 60 > /sys/class/gpio/export
  sleep 1
fi

case "$1" in
  red)
    echo high > /sys/class/gpio/gpio60/direction
    echo low > /sys/class/gpio/gpio26/direction
    ;;
  green)
    echo low > /sys/class/gpio/gpio60/direction
    echo high > /sys/class/gpio/gpio26/direction
    ;;
  orange)
    echo low > /sys/class/gpio/gpio60/direction
    echo low > /sys/class/gpio/gpio26/direction
    ;;
  off)
    echo high > /sys/class/gpio/gpio60/direction
    echo high > /sys/class/gpio/gpio26/direction
    ;;
  *)
    echo "Usage: led.sh <red|green|orange|off>"
    exit 1
esac