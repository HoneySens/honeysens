from __future__ import absolute_import

import json
import threading
import time
from Crypto.PublicKey import RSA
from Crypto.Hash import SHA
from Crypto.Signature import PKCS1_v1_5

from . import hooks
from .utils import communication
from .utils import constants


def worker(config_dir, config, events, events_lock):
    try:
        sensor_id = config.get('general', 'sensor_id')
        key = RSA.importKey(open('{}/{}'.format(config_dir, config.get('general', 'keyfile'), 'r')).read())
    except Exception as e:
        print('Warning: Event processor couldn\'t be started ({})'.format(str(e)))

    while True:
        with events_lock:
            send_candidates = []
            # Process the event queue
            for src, e in events.iteritems():
                send_candidates += e
            # Process send the queue
            if len(send_candidates) > 0:
                print('Sending {} collected event(s) to the server'.format(len(send_candidates)))
                event_data = {
                    'sensor': sensor_id,
                    'events': communication.encode_data(json.dumps(send_candidates).encode('ascii'))
                }
                # Add message signature
                signer = PKCS1_v1_5.new(key)
                digest = SHA.new()
                digest.update(json.dumps(send_candidates).encode('utf-8'))
                sign = signer.sign(digest)
                event_data['signature'] = communication.encode_data(sign)
                # Submit events
                try:
                    communication.perform_https_request(config, config_dir, 'api/events',
                                                        communication.REQUEST_TYPE_POST,
                                                        post_data=event_data)
                    # Remove sent events from the event queue
                    for c in send_candidates:
                        if c['source'] in events:
                            events.pop(c['source'])
                except Exception as e:
                    print(str(e))
                hooks.execute_hook(constants.Hooks.ON_EVENT)
        time.sleep(2)


def start(config_dir, config, events, events_lock):
    print('Starting event processor')
    thread = threading.Thread(target=worker, args=(config_dir, config, events, events_lock))
    # TODO Replace this with signalling and a graceful shutdown
    thread.daemon = True
    thread.start()
