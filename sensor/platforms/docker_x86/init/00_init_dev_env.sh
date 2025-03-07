#!/usr/bin/env bash
# Initializes this container based on a HoneySens sensor repository mounted under /mnt
set -e
PATH=$PATH:/usr/local/bin  # npm installs its stuff to /usr/local

if [[ ! -f /mnt/platforms/docker_x86/Gruntfile.js ]]; then
    echo "Error: /mnt/platforms/docker_x86/Gruntfile.js not found, please mount the sensor sources under /mnt"
    exit 1
fi

echo "Installing sensor manager"
cp /mnt/platforms/docker_x86/Gruntfile.js /mnt/platforms/docker_x86/package.json /srv
npm install --prefix /srv
grunt default --base /srv --gruntfile /srv/Gruntfile.js --force

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

mkdir -p /etc/services.d/grunt-watch
cat > /etc/services.d/grunt-watch/run << DELIMITER
#!/usr/bin/with-contenv bash
echo "Grunt watch task: \$DEV_WATCH_TASK"
exec /usr/local/bin/grunt \$DEV_WATCH_TASK --base /srv --gruntfile /srv/Gruntfile.js --force
DELIMITER
chmod +x /etc/services.d/grunt-watch/run
