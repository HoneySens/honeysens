#!/usr/bin/env bash
# Initializes this container based on a HoneySens sensor repository mounted under /mnt
set -e

if [[ ! -f /mnt/manager/setup.py ]]; then
    echo "Error: /mnt/manager/setup.py not found, please mount the sensor sources under /mnt"
    exit 1
fi

echo "Installing sensor manager"
mkdir -p /srv/manager
ln -sfnv /mnt/manager/manager /srv/manager/manager
ln -sfnv /mnt/manager/setup.py /srv/manager/setup.py
python3 -m venv --system-site-packages /srv/manager/venv
/srv/manager/venv/bin/pip3 install -e /srv/manager

echo "Adding services"
cp -vr /mnt/platforms/docker_x86/services/cntlm /etc/services.d
cp -vr /mnt/platforms/docker_x86/services/docker /etc/services.d
cp -vr /mnt/platforms/docker_x86/services/manager /etc/services.d
cp -vr /mnt/platforms/docker_x86/services/wpa_supplicant /etc/services.d

echo "Adding shutdown scripts"
cp -vr /mnt/platforms/docker_x86/shutdown/* /etc/cont-finish.d/

echo "Copying docker daemon configuration"
mkdir -p /etc/docker
cp -vr /mnt/platforms/docker_x86/daemon.json /etc/docker/

echo "Activating development mode for sensor manager"
# The env var DEV_MODE is only set on the first restart (see below) to allow the
# sensor manager to perform initial network setup once. After that, with DEV_MODE set,
# it skips initial network setup on restart (which sould restart all running honeypot services).
echo 1 > /var/run/s6/container_environment/PYTHONDONTWRITEBYTECODE

mkdir -p /etc/services.d/dev-watch
cat > /etc/services.d/dev-watch/run << DELIMITER
#!/usr/bin/with-contenv bash
echo "Monitoring sensor manager sources for changes..."
echo true > /var/run/s6/container_environment/DEV_MODE
exec watchmedo shell-command --recursive --pattern '*.py' --command 's6-svc -wr -t -u /var/run/s6/services/manager/' /mnt/manager/manager/
DELIMITER
chmod +x /etc/services.d/dev-watch/run
