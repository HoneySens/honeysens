#!/usr/bin/env sh
export PYTHONDONTWRITEBYTECODE=1
pip3 install -e /mnt
rm -vr /mnt/*.egg-info
watchmedo auto-restart --recursive --pattern="*.py" --directory="/mnt" -- celery -A processor.processor worker -l debug -Q high,low -Ofair --uid 33 --prefetch-multiplier=1 --hsconfig=/srv/data/config.cfg
