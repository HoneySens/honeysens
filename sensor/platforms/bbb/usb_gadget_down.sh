#!/bin/bash
test "${IFACE}" = "usb0" && systemctl stop ssh
exit 0
