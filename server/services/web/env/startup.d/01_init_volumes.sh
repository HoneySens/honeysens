#!/bin/bash
set -e

# Initialize data directory with a new template from $HS_APP_PATH in case it looks uninitialized
if [[ ! -f ${HS_DATA_PATH}/config.cfg ]]; then
    echo "NOTICE: Initializing data volume with new template"
    mkdir -vp ${HS_DATA_PATH}/{CA,configs,firmware,upload}
    cp -v ${HS_APP_PATH}/env/openssl_ca.cnf ${HS_DATA_PATH}/CA/openssl_ca.cnf
    cp -v ${HS_APP_PATH}/api/conf/config.clean.cfg ${HS_DATA_PATH}/config.cfg
fi
