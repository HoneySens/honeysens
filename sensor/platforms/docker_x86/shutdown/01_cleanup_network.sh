#!/usr/bin/with-contenv sh
# After all services have stopped, remove the bridge interface for the service networks.
# Otherwise, it would persist within the host's network stack (if we use host networking) even after the container existed.
# The docker daemon will recreate that interface on the next startup automatically.
if [[ "${SERVICES_IFACE}" ]]; then
    echo "Removing bridge interface ${SERVICES_IFACE}"
    ip link set dev ${SERVICES_IFACE} down
    brctl delbr ${SERVICES_IFACE}
fi