import base64
import hashlib
import hmac
from io import BytesIO
import json
import os
import pycurl
import time

REQUEST_TYPE_HEAD = 0
REQUEST_TYPE_GET = 1
REQUEST_TYPE_POST = 2


def perform_https_request(config, config_dir, path, request_type, verify=True, post_data=None, file_descriptor=None, sign=True):
    content = BytesIO()
    headers = {}
    c = pycurl.Curl()
    # Force HTTP/1.1
    c.setopt(pycurl.HTTP_VERSION, pycurl.CURL_HTTP_VERSION_1_1)

    def parse_headers(header_line):
        header_line = header_line.decode('utf-8')
        if ':' not in header_line:
            return
        name, value = header_line.split(':', 1)
        headers[name.strip().lower()] = value.strip()

    # Request type
    request_body = ''
    request_method = ''
    if request_type == REQUEST_TYPE_HEAD:
        c.setopt(pycurl.HTTPGET, 1)
        c.setopt(pycurl.NOBODY, 1)
    elif request_type == REQUEST_TYPE_GET:
        request_method = 'get'
        c.setopt(pycurl.HTTPGET, 1)
    elif request_type == REQUEST_TYPE_POST:
        request_method = 'create'
        request_body = json.dumps(post_data)
        c.setopt(pycurl.POST, 1)
        c.setopt(pycurl.POSTFIELDS, request_body)

    # TLS server certificate verification
    if verify:
        c.setopt(pycurl.SSL_VERIFYPEER, 1)
        c.setopt(pycurl.SSL_VERIFYHOST, 2)
    else:
        c.setopt(pycurl.SSL_VERIFYPEER, 0)
        c.setopt(pycurl.SSL_VERIFYHOST, 0)

    # Sign request by adding HMAC headers
    if sign:
        now = int(time.time())
        msg = f'{now} {request_method} {request_body}'
        signature = hmac.new(config.get('general', 'secret').encode('utf-8'), msg.encode('utf-8'), hashlib.sha256).hexdigest()
        mac_headers = [f'x-hs-auth: {signature}',
                       f'x-hs-sensor: {config.get("general", "sensor_id")}',
                       f'x-hs-ts: {now}',
                       'x-hs-type: sha256']
        c.setopt(pycurl.HTTPHEADER, mac_headers)

    # Proxy configuration
    # Currently we only support NTLM through cntlm
    if config.get('proxy', 'mode') == '1':
        c.setopt(pycurl.PROXY, '127.0.0.1')
        c.setopt(pycurl.PROXYPORT, 3128)

    # Target output
    if file_descriptor is not None:
        file_descriptor.seek(0)
        c.setopt(pycurl.WRITEDATA, file_descriptor)
    else:
        c.setopt(pycurl.WRITEDATA, content)

    # Set a custom CA cert in case the config provides one
    server_cert_path = '{}/{}'.format(config_dir, config.get('server', 'certfile'))
    if os.path.isfile(server_cert_path) and os.path.getsize(server_cert_path) > 0:
        c.setopt(pycurl.CAINFO, server_cert_path)

    c.setopt(pycurl.URL, 'https://{}:{}/{}'.format(config.get('server', 'name'), config.get('server', 'port_https'), path))
    c.setopt(pycurl.HEADERFUNCTION, parse_headers)
    c.perform()

    status_code = c.getinfo(pycurl.HTTP_CODE)
    c.close()

    # Verify response signature
    if sign:
        try:
            if headers['x-hs-type'] not in ['sha256']:
                raise ValueError(f'Unsupported algorithm {headers["x-hs-type"]}')
            msg = f'{headers["x-hs-ts"]} {request_method} '.encode('utf-8')
            msg += content.getvalue()
            signature = hmac.new(config.get('general', 'secret').encode('utf-8'), msg, getattr(hashlib, headers['x-hs-type'])).hexdigest()
            if signature != headers['x-hs-auth']:
                raise ValueError(f'Invalid signature')
        except Exception as e:
            raise Exception(f'Received no or invalid HMAC headers ({str(e)})')

    return {'status': status_code, 'headers': headers, 'content': content.getvalue()}


def encode_data(data):
    return base64.b64encode(data).decode('utf-8')
