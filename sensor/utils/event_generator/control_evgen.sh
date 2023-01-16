#!/bin/bash
set -e
if [ ! "$(docker ps -a -q -f name=evgen)" ]; then
  docker run -d --name evgen --restart always honeysens/evgen:latest -i 192.168.2.101 -i 192.168.2.102
  sleep 2
fi
status="$(docker ps -q -f name=evgen -f status=running)"
if [ -z "$status" ]; then
  echo "HoneySens event generator is currently NOT running"
  read -n 1 -p "Start the event generator (y/n)?" answer
  if [ "$answer" = "y" ]; then
    echo -e "\nStarting event generator..."
    docker start evgen
  fi
else
  echo "HoneySens event generator is currently RUNNING"
  read -n 1 -p "Stop the event generator (y/n)?" answer
  if [ "$answer" = "y" ]; then
    echo -e "\nStopping event generator..."
    docker stop evgen
  fi
fi
