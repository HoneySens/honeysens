#!/usr/bin/env bash
set -e
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
    rm -r /srv/Gruntfile.js /srv/package.json /srv/package-lock.json /srv/node_modules/
    kill 1
    exit
fi

if [[ ! -f /srv/data/config.cfg ]]; then
    echo "Adjusting HoneySens configuration"
    cp -v /srv/data/config.clean.cfg /srv/data/config.cfg
    sed -i -e 's/debug.*/debug = true/' /srv/data/config.cfg
    chmod a+w /srv/data/config.cfg
fi

mkdir -p /srv/startup.d
echo "Initializing volumes if necessary"
cp -v /srv/utils/docker/startup.d/01_init_volumes.sh /srv/startup.d/
ln -sf /srv/startup.d/01_init_volumes.sh /etc/startup.d/01_init_volumes.sh
/srv/startup.d/01_init_volumes.sh

echo "Creating certificates if necessary"
cp -v /srv/utils/docker/startup.d/02_regen_honeysens_ca.sh /srv/startup.d/
cp -v /srv/utils/docker/startup.d/03_regen_https_cert.sh /srv/startup.d/
ln -sf /srv/startup.d/02_regen_honeysens_ca.sh /etc/startup.d/02_regen_honeysens_ca.sh
ln -sf /srv/startup.d/03_regen_https_cert.sh /etc/startup.d/03_regen_https_cert.sh
sed -i -e 's#/opt/HoneySens/#/srv/#g' /srv/startup.d/02_regen_honeysens_ca.sh /srv/startup.d/03_regen_https_cert.sh
/srv/startup.d/02_regen_honeysens_ca.sh
/srv/startup.d/03_regen_https_cert.sh

echo "Configuring Apache web server"
cp -v /srv/utils/docker/apache.ports.conf /etc/apache2/ports.conf
cp -v /srv/utils/docker/apache.http.api.conf /etc/apache2/sites-available/honeysens_http_api.conf
cp -v /srv/utils/docker/apache.http.redirect.conf /etc/apache2/sites-available/honeysens_http_redirect.conf
cp -v /srv/utils/docker/apache.ssl.conf /etc/apache2/sites-available/honeysens_ssl.conf
cp -v /srv/utils/docker/apache.public.conf /etc/apache2/conf/honeysens.public.conf
sed -i -e 's#/opt/HoneySens/#/srv/#g' /etc/apache2/sites-available/*.conf
cp -v /srv/utils/docker/startup.d/04_init_apache.sh /srv/startup.d/
ln -sf /srv/startup.d/04_init_apache.sh /etc/startup.d/04_init_apache.sh
/srv/startup.d/04_init_apache.sh

echo "Running grunt-watch (${DEV_WATCH_TASK}) to monitor filesystem for changes"
/usr/local/bin/grunt ${DEV_WATCH_TASK} --base /srv --gruntfile /srv/Gruntfile.js --src="/mnt" --dst="/srv" --force &

echo "Launching httpd"
cp -v /srv/utils/docker/startup.d/06_launch_httpd.sh /srv/startup.d/
ln -sf /srv/startup.d/06_launch_httpd.sh /etc/startup.d/06_launch_httpd.sh
/srv/startup.d/06_launch_httpd.sh &

until curl -q -k https://127.0.0.1:8443/api/system/identify
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
