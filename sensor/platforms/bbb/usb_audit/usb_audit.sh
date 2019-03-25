#!/usr/bin/env bash

cleanup() {
  echo "Cleaning up"
  umount ${MOUNT_PATH} 2>/dev/null
  rmdir -v ${MOUNT_PATH}
  exit 0
}

if [[ -z "$1" ]]; then
  echo "Usage: usb_audit.sh <device_name>"
  exit 1
fi

if [[ ! -b /dev/${1} ]]; then
  echo "/dev/${1} is no block device, exiting."
  exit 0
fi

MOUNT_PATH=/mnt/${1}
OUT_DIR=${MOUNT_PATH}/sensor-$(date "+%F.%H-%m")

# Attempt to unmount previous artifacts
umount ${MOUNT_PATH} 2>/dev/null

# Prepare mount path
mkdir -p ${MOUNT_PATH}

# Mount attempt. Cleanly quit if the partition is not mountable.
echo "Attempting to mount ${1} to ${MOUNT_PATH}"
mount /dev/${1} ${MOUNT_PATH} || cleanup

echo "Mounting successful, attempting to write log data"
if [[ -w ${MOUNT_PATH} ]]; then
  mkdir -p ${OUT_DIR}
  journalctl --no-pager >${OUT_DIR}/system.log
else
  echo "${MOUNT_PATH} is not writable"
fi
cleanup