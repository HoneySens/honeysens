from __future__ import absolute_import

import base64
import pycurl
import json
from Crypto.PublicKey import RSA
from Crypto.Hash import SHA
from Crypto.Signature import PKCS1_v1_5
from io import BytesIO

REQUEST_TYPE_HEAD = 0
REQUEST_TYPE_GET = 1
REQUEST_TYPE_POST = 2


def perform_https_request(config, config_dir, path, request_type, verify=True, post_data=None, file_descriptor=None):
    content = BytesIO()
    headers = {}
    c = pycurl.Curl()

    def parse_headers(header_line):
        if ':' not in header_line:
            return
        name, value = header_line.split(':', 1)
        headers[name.strip().lower()] = value.strip()

    # Request type
    if request_type == REQUEST_TYPE_HEAD:
        c.setopt(pycurl.HTTPGET, 1)
        c.setopt(pycurl.NOBODY, 1)
    elif request_type == REQUEST_TYPE_GET:
        c.setopt(pycurl.HTTPGET, 1)
    elif request_type == REQUEST_TYPE_POST:
        c.setopt(pycurl.POST, 1)
        c.setopt(pycurl.POSTFIELDS, json.dumps(post_data))

    # TLS certificate verification
    if verify:
        c.setopt(pycurl.SSL_VERIFYPEER, 1)
        c.setopt(pycurl.SSL_VERIFYHOST, 2)
    else:
        c.setopt(pycurl.SSL_VERIFYPEER, 0)
        c.setopt(pycurl.SSL_VERIFYHOST, 0)

    # Client certificate
    if verify:
        c.setopt(pycurl.SSLCERT, '{}/{}'.format(config_dir, config.get('general', 'certfile')))
        c.setopt(pycurl.SSLKEY, '{}/{}'.format(config_dir, config.get('general', 'keyfile')))

    # Proxy configuration
    # Currently we only support NTLM through cntlm
    if config.get('proxy', 'mode') == '1':
        c.setopt(pycurl.PROXY, '127.0.0.1')
        c.setopt(pycurl.PROXYPORT, 3128)

    # Target output
    if file_descriptor is not None:
        c.setopt(pycurl.WRITEFUNCTION, file_descriptor.write)
    else:
        c.setopt(pycurl.WRITEFUNCTION, content.write)

    c.setopt(pycurl.URL, 'https://{}:{}/{}'.format(config.get('server', 'name'), config.get('server', 'port_https'), path))
    c.setopt(pycurl.CAINFO, '{}/{}'.format(config_dir, config.get('server', 'certfile')))
    c.setopt(pycurl.HEADERFUNCTION, parse_headers)
    c.perform()

    status_code = c.getinfo(pycurl.HTTP_CODE)
    c.close()

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
