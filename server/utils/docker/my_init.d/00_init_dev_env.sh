#!/usr/bin/env bash
# Initializes this container based on a HoneySens server repository mounted under /mnt

if [[ ! -f /mnt/Gruntfile.js ]]; then
    echo "Error: /mnt/Gruntfile.js not found, please mount the server sources under /mnt"
    exit 1
fi

# The environment variable BUILD_ONLY can be set if this container is supposed to just assemble the project
# in release configuration to /srv and then exit.
if [[ -z "$BUILD_ONLY" ]]; then
    GRUNT_TARGET="default"
else
    GRUNT_TARGET="release"
fi

echo "Grunt target: $GRUNT_TARGET"
echo "Assembling project in /srv"
cp /mnt/Gruntfile.js /mnt/package.json /srv
npm install --prefix /srv
grunt ${GRUNT_TARGET} --base /srv --gruntfile /srv/Gruntfile.js --src="/mnt" --dst="/srv" --force

if [[ ! -z "$BUILD_ONLY" ]]; then
    echo "BUILD_ONLY is set, removing build artifacts and exiting"
    # Remove build artifacts
    rm -r /srv/Gruntfile.js /srv/package.json /srv/node_modules/ /srv/etc/
    kill 1
    exit
fi

if [[ ! -f /srv/data/config.cfg ]]; then
    echo "Adjusting HoneySens configuration"
    cp -v /srv/data/config.clean.cfg /srv/data/config.cfg
    sed -i -e 's/debug.*/debug = true/' /srv/data/config.cfg
    chown www-data:www-data /srv/data/config.cfg
    chmod a+w /srv/data/config.cfg
fi

echo "Adding services"
cp -vr /srv/utils/docker/services/apache2 /etc/service
mkdir -p /etc/service/grunt-watch
cat > /etc/service/grunt-watch/run << DELIMITER
#!/bin/bash
echo "Grunt watch task: \$DEV_WATCH_TASK"
exec /usr/local/bin/grunt \$DEV_WATCH_TASK --base /srv --gruntfile /srv/Gruntfile.js --src="/mnt" --dst="/srv" --force
DELIMITER
chmod +x /etc/service/grunt-watch/run
mkdir -p /etc/service/motd
cat > /etc/service/motd/run << DELIMITER
#!/bin/bash
until curl -q -k https://127.0.0.1/api/system/identify
do
	echo "Waiting for the API..."
	sleep 2
done
figlet HoneySens
echo -e "\n                 Development Server\n"
boxes -d stone << BOXES
The server is now assembled and ready. If you apply changes
to PHP or HTML/CSS/JS sources, the respective modules will
be rebuilt by this server automatically. For any other
change, you currently have to restart this server.
BOXES
sleep infinity
DELIMITER
chmod +x /etc/service/motd/run

mkdir -p /srv/my_init.d
echo "Initializing volumes if necessary"
cp -v /srv/utils/docker/my_init.d/01_init_volumes.sh /srv/my_init.d/
/srv/my_init.d/01_init_volumes.sh

echo "Creating certificates if necessary"
cp -v /srv/utils/docker/my_init.d/02_regen_honeysens_ca.sh /srv/my_init.d/
cp -v /srv/utils/docker/my_init.d/03_regen_https_cert.sh /srv/my_init.d/
sed -i -e 's#/opt/HoneySens/#/srv/#g' /srv/my_init.d/02_regen_honeysens_ca.sh /srv/my_init.d/03_regen_https_cert.sh
/srv/my_init.d/02_regen_honeysens_ca.sh
/srv/my_init.d/03_regen_https_cert.sh

echo "Adjusting permissions so that /srv/data is writeable for the web server"
chown -R www-data:www-data /srv/data
chmod -R 777 /srv/data

echo "Adjusting sudo configuration"
cp -v /srv/utils/docker/sudoers.conf /etc/sudoers.d/honeysens

echo "Configuring Apache web server"
cp -v /srv/utils/docker/apache.http.conf /etc/apache2/sites-available/honeysens_http.conf
cp -v /srv/utils/docker/apache.ssl.conf /etc/apache2/sites-available/honeysens_ssl.conf
sed -i -e 's#/opt/HoneySens/#/srv/#g' /etc/apache2/sites-available/*.conf
cp -v /srv/utils/docker/my_init.d/05_init_apache.sh /srv/my_init.d/
/srv/my_init.d/05_init_apache.sh
