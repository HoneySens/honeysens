#!/usr/bin/env sh
export PYTHONDONTWRITEBYTECODE=1
pip3 install -e /mnt
rm -vr /mnt/*.egg-info
watchmedo auto-restart --recursive --pattern="*.py" --directory="/mnt" -- /home/hs/.local/bin/celery -A processor.processor worker -B -s /srv/data/tasks/celerybeat-schedule -l debug -Q high,low -Ofair --prefetch-multiplier=1 --hsconfig=/srv/data/config.cfg