#!/usr/bin/env bash
set -e
# Initializes this container with a mounted API and frontend source tree
HS_API_PATH=${HS_APP_PATH}/api
HS_ENV_PATH=${HS_APP_PATH}/env
HS_FRONTEND_PATH=${HS_APP_PATH}/frontend

if [[ ! ( -d ${HS_API_PATH} && -d ${HS_ENV_PATH} && -d ${HS_FRONTEND_PATH}) ]]; then
    echo "Error: Application sources not mounted at ${HS_APP_PATH}"
    exit 1
fi

echo "[*] Installing API dependencies"
mkdir -vp /srv/api ${HS_API_PATH}/build/vendor
ln -sfv ${HS_API_PATH}/app/composer.json /srv/api/composer.json
ln -sfv ${HS_API_PATH}/app/composer.lock /srv/api/composer.lock
ln -sfv ${HS_API_PATH}/build/vendor /srv/api/vendor
ln -sfv ${HS_API_PATH}/app /srv/api/app
composer -d /srv/api install

echo "[*] Installing frontend dependencies"
mkdir -vp /srv/frontend
for p in app assets main.js package-lock.json package.json vendor webpack.config.js; do
  ln -sfv ${HS_FRONTEND_PATH}/${p} /srv/frontend/${p}
done
ln -sfv ${HS_FRONTEND_PATH}/build/dist /srv/frontend/dist
npm --prefix /srv/frontend install

# The environment variable BUILD_ONLY can be set if this container
# is supposed to just build frontend and API, then exit.
if [[ -n "${BUILD_ONLY}" ]]; then
    echo "[*] Building frontend"
    (cd /srv/frontend && webpack)
    exit
fi

echo "[*] Initializing data volume"
${HS_ENV_PATH}/startup.d/01_init_volumes.sh
sed -i -e 's/debug.*/debug = true/' ${HS_DATA_PATH}/config.cfg
chmod a+w ${HS_DATA_PATH}/config.cfg

echo "[*] (Re)creating CA and certificates on demand"
${HS_ENV_PATH}/startup.d/02_regen_honeysens_ca.sh
${HS_ENV_PATH}/startup.d/03_regen_https_cert.sh

echo "[*] Serving API"
cp -v ${HS_ENV_PATH}/apache/apache.http.api.conf /etc/apache2/sites-available/honeysens_http_api.conf
cp -v ${HS_ENV_PATH}/apache/apache.http.redirect.conf /etc/apache2/sites-available/honeysens_http_redirect.conf
cp -v ${HS_ENV_PATH}/apache/apache.https.conf /etc/apache2/sites-available/honeysens_https.conf
cp -v ${HS_ENV_PATH}/apache/apache.frontend.proxy.conf /etc/apache2/conf/frontend.conf
cp -v ${HS_ENV_PATH}/apache/apache.web.conf /etc/apache2/conf/web.conf

sed -i -e "s#/srv/api/#${HS_API_PATH}/#g" /etc/apache2/sites-available/*.conf
a2enmod proxy_wstunnel
${HS_ENV_PATH}/startup.d/04_init_apache.sh
${HS_ENV_PATH}/startup.d/06_launch_httpd.sh &

echo "[*] Serving frontend"
(cd /srv/frontend && webpack serve) &

sleep 5  # Let the other services settle
until curl -sko /dev/null https://127.0.0.1:8443/api/system/identify
do
	echo "Waiting for the API..."
	sleep 2
done
figlet HoneySens
echo -e "\n                 Development Server\n"
boxes -d stone << BOXES
The server is now assembled and ready. If you apply changes
to PHP or HTML/CSS/JS sources, the respective modules will
be rebuilt by the server automatically. For any other
change, you currently have to re-initialize the dev environment.
BOXES
sleep infinity
