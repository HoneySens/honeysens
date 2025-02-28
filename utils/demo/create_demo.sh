#!/usr/bin/env bash
set -e

ADMIN_EMAIL="test@example.com"
ADMIN_PASSWORD="honeysens"
DIVISION_NAME="Demo"
SENSOR_NAME="sensor1"
SENSOR_LOCATION="local"
SENSOR_SERVICES="recon cowrie"

if [[ -z "${1}" || -z "${2}" ]]; then
  echo "Usage: create_demo.sh server_revision sensor_revision"
  exit 1
fi

BUILDDIR="./build"
BUILD_ID=`date +%Y%m%d`
SERVER_REVISION="${1}-${BUILD_ID}"
SENSOR_REVISION="${2}"

rm -rf ${BUILDDIR}
mkdir -p ${BUILDDIR}
cp docker-compose-create.yml docker-compose.yml ${BUILDDIR}
sed "s/\$SERVER_REVISION/${SERVER_REVISION}/" docker-compose-create.yml > ${BUILDDIR}/docker-compose-create.yml
sed -e "s/\$SERVER_REVISION/${SERVER_REVISION}/" -e "s/\$SENSOR_REVISION/${SENSOR_REVISION}/" -e "s/\$BUILD_ID/${BUILD_ID}/" docker-compose.yml > ${BUILDDIR}/docker-compose.yml

echo "Building server..."
make -C ../../server/ dist BUILD_ID=${BUILD_ID} REVISION=${SERVER_REVISION}

echo "Building sensor..."
make -C ../../sensor/platforms/docker_x86/ dist REVISION=${SENSOR_REVISION}

for service in ${SENSOR_SERVICES}; do
  echo "Building ${service} service..."
  make -C ../../sensor/services/${service}/ amd64
done

echo "Launching server..."
docker compose -f ${BUILDDIR}/docker-compose-create.yml up -d

echo "Waiting for API at https://localhost..."
until [ "$(curl -sk https://localhost/api/system/identify)" == "HoneySens" ]; do
  sleep 1
done

echo "Performing initial server setup..."
curl -skH "Content-Type: application/json" -d "{\"email\":\"${ADMIN_EMAIL}\",\"password\":\"${ADMIN_PASSWORD}\",\"serverEndpoint\":\"web\",\"divisionName\":\"${DIVISION_NAME}\"}" -o /dev/null https://localhost/api/system/install

echo "Obtaining session cookie..."
COOKIES=`curl -skH "Content-Type: application/json" -c - -o /dev/null -d "{\"username\":\"admin\",\"password\":\"${ADMIN_PASSWORD}\"}" https://localhost/api/sessions`

echo "Updating server endpoint..."
SERVER_SETTINGS=`echo "${COOKIES}" | curl -sk -b - https://localhost/api/settings | jq '.serverPortHTTPS=8443 | .sensorsUpdateInterval=2'`
echo "${COOKIES}" | curl -skX PUT -H "Content-Type: application/json" -d "${SERVER_SETTINGS}" -b - -o /dev/null https://localhost/api/settings

for service in ${SENSOR_SERVICES}; do
  echo "Uploading $service service..."
  TASK_ID=`echo "${COOKIES}" | curl -skH "Content-Type: multipart/form-data" -b - -F chunkCount=1 -F chunkIndex=0 -F "fileBlob=@$(realpath ../../sensor/services/$service/build/dist/*);filename=service.tar.gz" -F token=token -F fileName=service.tar.gz -F fileSize=1 https://localhost/api/tasks/upload | jq -r .task.id`
  while [ `echo "${COOKIES}" | curl -sk -b - https://localhost/api/tasks/${TASK_ID} | jq .status` -ne 2 ]; do sleep 1; done
  TASK_ID=`echo "${COOKIES}" | curl -skH "Content-Type: application/json" -b - -d "{\"task\":${TASK_ID}}" https://localhost/api/services | jq -r .id`
  while [ `echo "${COOKIES}" | curl -sk -b - https://localhost/api/tasks/${TASK_ID} | jq .status` -ne 2 ]; do sleep 1; done
  echo "${COOKIES}" | curl -skX DELETE -b - -o /dev/null https://localhost/api/tasks/${TASK_ID}
done

echo "Creating sensor..."
SERVICES=`echo "${COOKIES}" | curl -sk -b - https://localhost/api/services | jq '[.[] | {"service": .id, "revision": null}]'`
SENSOR_PARAMS="{\"name\":\"${SENSOR_NAME}\",\"location\":\"${SENSOR_LOCATION}\",\"division\":1,\"eapol_mode\":0,\"server_endpoint_mode\":0,\"network_ip_mode\":2,\"network_mac_mode\":0,\"proxy_mode\":0,\"update_interval\":null,\"service_network\":null,\"firmware\":null,\"services\":${SERVICES}}"
SENSOR_ID=`echo "${COOKIES}" | curl -skH "Content-Type: application/json" -b - -d "${SENSOR_PARAMS}" https://localhost/api/sensors | jq .id`
echo "${COOKIES}" | curl -skH "Content-Type: application/json" -X PUT -b - -o /dev/null -d "${SENSOR_PARAMS}" https://localhost/api/sensors/${SENSOR_ID}

echo "Retrieving sensor config..."
TASK_ID=`echo "${COOKIES}" | curl -sk -b - https://localhost/api/sensors/config/${SENSOR_ID} | jq .id`
while [ `echo "${COOKIES}" | curl -sk -b - https://localhost/api/tasks/${TASK_ID} | jq .status` -ne 2 ]; do sleep 1; done
echo "${COOKIES}" | curl -sk -b - -o "${BUILDDIR}/config.tar.gz" https://localhost/api/tasks/${TASK_ID}/result/1

echo "Taking server snapshot..."
docker compose -f ${BUILDDIR}/docker-compose-create.yml stop web tasks broker registry
docker compose -f ${BUILDDIR}/docker-compose-create.yml exec -T backup backup > "${BUILDDIR}/snapshot.tar.bz2"

echo "Building initializer..."
docker build -t honeysens/demo-init:${BUILD_ID} --build-arg SNAPSHOT_PATH=`realpath --relative-to=$(pwd) ${BUILDDIR}`/snapshot.tar.bz2 --build-arg SENSOR_CONFIG_PATH=`realpath --relative-to=$(pwd) ${BUILDDIR}`/config.tar.gz .

echo "Writing ${BUILDDIR}/demo-init-${BUILD_ID}.tar..."
docker save -o ${BUILDDIR}/demo-init-${BUILD_ID}.tar honeysens/demo-init:${BUILD_ID}

echo "Shutting down server, cleaning up..."
docker compose -f ${BUILDDIR}/docker-compose-create.yml stop
docker compose -f ${BUILDDIR}/docker-compose-create.yml down -v
rm -vr ${BUILDDIR}/snapshot.tar.bz2 ${BUILDDIR}/config.tar.gz ${BUILDDIR}/docker-compose-create.yml

echo "Done"
