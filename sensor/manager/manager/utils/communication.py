import base64
import hashlib
import hmac
from io import BytesIO
import json
import pycurl
import time

from Cryptodome.PublicKey import RSA
from Cryptodome.Hash import SHA
from Cryptodome.Signature import PKCS1_v1_5

REQUEST_TYPE_HEAD = 0
REQUEST_TYPE_GET = 1
REQUEST_TYPE_POST = 2


def perform_https_request(config, config_dir, path, request_type, verify=True, post_data=None, file_descriptor=None, sign=True, retry_with_client_cert=False):
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

    # Request signature
    if sign:
        if config.get('general', 'secret') is None or retry_with_client_cert:
            # Use TLS client certificate
            c.setopt(pycurl.SSLCERT, '{}/{}'.format(config_dir, config.get('general', 'certfile')))
            c.setopt(pycurl.SSLKEY, '{}/{}'.format(config_dir, config.get('general', 'keyfile')))
        else:
            # Add HMAC headers
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

    c.setopt(pycurl.URL, 'https://{}:{}/{}'.format(config.get('server', 'name'), config.get('server', 'port_https'), path))
    c.setopt(pycurl.CAINFO, '{}/{}'.format(config_dir, config.get('server', 'certfile')))
    c.setopt(pycurl.HEADERFUNCTION, parse_headers)
    c.perform()

    status_code = c.getinfo(pycurl.HTTP_CODE)
    c.close()

    # Safeguard: Fall back to TLS client cert in case a signed request with HMACs fails
    if status_code != 200 and not retry_with_client_cert:
        return perform_https_request(config, config_dir, path, request_type, verify, post_data, file_descriptor, sign, True)

    # Verify response signature
    if sign and config.get('general', 'secret') is not None and not retry_with_client_cert:
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


def sign_data(key, data):
    key = RSA.importKey(key)
    signer = PKCS1_v1_5.new(key)
    digest = SHA.new()
    digest.update(json.dumps(data).encode('utf-8'))
    sign = signer.sign(digest)
    return encode_data(sign)


def encode_data(data):
    return base64.b64encode(data).decode('utf-8')
