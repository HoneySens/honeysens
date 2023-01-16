# HoneySens Event Generator
Python utility that continuously generates pseudo-random events by sending network traffic to deployed sensors that targets supported honeypot services.

Build with `docker build -t honeysens/evgen .`, then edit the `docker run` command in `control_evgen.sh` to match the local deployment:
* Add each target sensor IP address as `-i <ip>` or `--ip <ip>`
* Optionally, specify the minimum and maximum sleep intervals between generating events as `--sleep-min <minutes>` and `--sleep-max <minutes>`

Then execute `control_evgen.sh` to start or stop the event generating container on demand. It will create the container if it doesn't exist and then ask whether to start or stop it. The container is also created with `--restart always`, which will always start the container on boot.