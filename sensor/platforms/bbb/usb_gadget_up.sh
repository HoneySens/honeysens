#!/bin/bash
test "${IFACE}" = "usb0" && systemctl start ssh
exit 0
