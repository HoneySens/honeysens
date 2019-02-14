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

echo "Configuring Apache web server"
cp -v /srv/utils/docker/apache.* /etc/apache2/sites-available/
sed -i -e 's#/opt/HoneySens/#/srv/#g' /etc/apache2/sites-available/apache.ssl.conf /etc/apache2/sites-available/apache.http.conf
a2ensite apache.http apache.ssl

if [[ ! -f /srv/data/config.cfg ]]; then
    echo "Adjusting HoneySens configuration"
    cp -v /srv/data/config.clean.cfg /srv/data/config.cfg
    sed -i -e 's/password.*/password = honeysens/' -e 's#certfile.*#certfile = /srv/data/https.chain.crt#' -e 's#app_path.*#app_path = /srv#' -e 's/debug.*/debug = true/' /srv/data/config.cfg
    chown www-data:www-data /srv/data/config.cfg
    chmod a+w /srv/data/config.cfg
fi

echo "Adding services"
cp -vr /srv/utils/docker/services/apache2 /etc/service
cp -vr /srv/utils/docker/services/mysql /etc/service
cp -vr /srv/utils/docker/services/beanstalkd /etc/service
cp -vr /srv/utils/docker/services/sensorcfg-creation-worker /etc/service
cp -vr /srv/utils/docker/services/update-worker /etc/service
cp -vr /srv/utils/docker/services/service-registry-worker /etc/service
sed -i -e 's#/opt/HoneySens/#/srv/#g' /etc/service/sensorcfg-creation-worker/run /etc/service/update-worker/run /etc/service/service-registry-worker/run
mkdir /etc/service/grunt-watch
cat > /etc/service/grunt-watch/run << DELIMITER
#!/bin/bash
echo "Grunt watch task: \$DEV_WATCH_TASK"
exec /usr/local/bin/grunt \$DEV_WATCH_TASK --base /srv --gruntfile /srv/Gruntfile.js --src="/mnt" --dst="/srv" --force
DELIMITER
chmod +x /etc/service/grunt-watch/run
mkdir /etc/service/motd
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

echo "Initializing database volume if necessary"
cp -v /srv/utils/docker/my_init.d/01_init_volumes.sh /etc/my_init.d/
/etc/my_init.d/01_init_volumes.sh

echo "Creating certificates if necessary"
cp -v /srv/utils/docker/my_init.d/02_regen_honeysens_ca.sh /etc/my_init.d/
cp -v /srv/utils/docker/my_init.d/03_regen_https_cert.sh /etc/my_init.d/
cp -v /srv/utils/docker/my_init.pre_shutdown.d/01_stop_mysql.sh /etc/my_init.pre_shutdown.d/
sed -i -e 's#/opt/HoneySens/#/srv/#g' /etc/my_init.d/02_regen_honeysens_ca.sh /etc/my_init.d/03_regen_https_cert.sh
/etc/my_init.d/02_regen_honeysens_ca.sh
/etc/my_init.d/03_regen_https_cert.sh

echo "Adjusting permissions so that /srv/data is writeable for the web server"
chown -R www-data:www-data /srv/data
chmod -R 777 /srv/data

echo "Adjusting sudo configuration"
cp -v /srv/utils/docker/sudoers.conf /etc/sudoers.d/honeysens
