#!/usr/bin/env bash
set -e
# Initializes this container with mounted API and frontend source trees

if [[ ! -d ${HS_API_PATH} ]]; then
    echo "Error: API sources not mounted at ${HS_API_PATH}"
    exit 1
fi
if [[ ! -d ${HS_FRONTEND_PATH} ]]; then
    echo "Error: Frontend sources not mounted at ${HS_FRONTEND_PATH}"
    exit 1
fi

echo "[*] Initialize data volume"
${HS_API_PATH}/env/startup.d/01_init_volumes.sh
sed -i -e 's/debug.*/debug = true/' ${HS_DATA_PATH}/config.cfg
chmod a+w ${HS_DATA_PATH}/config.cfg

echo "[*] (Re)create CA and certificates on demand"
${HS_API_PATH}/env/startup.d/02_regen_honeysens_ca.sh
${HS_API_PATH}/env/startup.d/03_regen_https_cert.sh

echo "[*] Serving API in debug mode"
# Assemble API project
mkdir -vp /srv/api ${HS_API_PATH}/build/vendor
ln -sfv ${HS_API_PATH}/app/composer.json /srv/api/composer.json
ln -sfv ${HS_API_PATH}/app/composer.lock /srv/api/composer.lock
ln -sfv ${HS_API_PATH}/build/vendor /srv/api/vendor
ln -sfv ${HS_API_PATH}/app /srv/api/app
composer -d /srv/api install
# Configure and launch apache web server
cp -v ${HS_API_PATH}/env/apache.http.api.conf /etc/apache2/sites-available/honeysens_http_api.conf
cp -v ${HS_API_PATH}/env/apache.http.redirect.conf /etc/apache2/sites-available/honeysens_http_redirect.conf
cp -v ${HS_API_PATH}/env/apache.ssl.conf /etc/apache2/sites-available/honeysens_ssl.conf
sed -i -e "s#/opt/HoneySens/#${HS_API_PATH}/#g" /etc/apache2/sites-available/*.conf
${HS_API_PATH}/env/startup.d/04_init_apache.sh
${HS_API_PATH}/env/startup.d/06_launch_httpd.sh &

echo "[*] Serving frontend in development mode"
mkdir -vp /srv/frontend
for p in app assets main.js package-lock.json package.json vendor webpack.config.js; do
  ln -sfv ${HS_FRONTEND_PATH}/${p} /srv/frontend/${p}
done
npm --prefix /srv/frontend install
(cd /srv/frontend && webpack serve) &

until curl -s -k https://127.0.0.1:8443/api/system/identify
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
