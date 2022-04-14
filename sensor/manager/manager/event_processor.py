import collections
import json
import logging
import queue
import threading

from . import hooks
from . import polling
from .utils import communication
from .utils import constants


# Maximum number of events this instance can hold in a ring buffer
EVENT_CACHE_LENGTH = 1024


class EventProcessor(threading.Thread):

    config = None
    config_dir = None
    ev_stop = None
    logger = None
    queue = None
    events = collections.deque([], EVENT_CACHE_LENGTH)

    def __init__(self, queue, config, config_dir):
        threading.Thread.__init__(self)
        self.config = config
        self.config_dir = config_dir
        self.ev_stop = threading.Event()
        self.queue = queue
        self.logger = logging.getLogger(__name__)
        self.logger.info('Initializing event processor')

    def run(self):
        try:
            sensor_id = self.config.get('general', 'sensor_id')
        except Exception as e:
            self.logger.error('Event processor couldn\'t be started ({})'.format(str(e)))
            return

        while not self.ev_stop.is_set():
            try:
                event = self.queue.get(True, 1)
            except queue.Empty:
                # Submit events ASAP if there are no further queued events to process
                self.submit_events(sensor_id)
                continue
            self.events.append(event)
            self.logger.info('Event received, queue length: {}'.format(len(self.events)))
            self.queue.task_done()
            # If the queue is full, attempt to submit events.
            # If this fails, further events will overwrite queued events in the cache.
            if len(self.events) == self.events.maxlen:
                self.submit_events(sensor_id)
            hooks.execute_hook(constants.Hooks.ON_EVENT)
        self.logger.info('Stopping event processor')

    def stop(self):
        self.ev_stop.set()

    def submit_events(self, sensor_id):
        # Attempt to send queued events
        if polling.is_online() and len(self.events) > 0:
            send_cand = [e for e in self.events]
            self.logger.info('Attempting to send {} event(s) to the server'.format(len(send_cand)))
            event_data = {
                'sensor': sensor_id,
                'events': communication.encode_data(json.dumps(send_cand).encode('ascii'))
            }
            # Submit events
            try:
                result = communication.perform_https_request(self.config, self.config_dir, 'api/events',
                                                             communication.REQUEST_TYPE_POST,
                                                             post_data=event_data)
                if result['status'] != 200:
                    raise Exception('Server response {}: {}'.format(result['status'], result['content']))
            except Exception as e:
                self.logger.warning('Could not submit events ({})'.format(str(e)))
                polling.trigger_conn_error()
                return
            # Remove successfully sent events from the event queue
            for c in send_cand:
                self.events.remove(c)
            self.logger.debug('Event queue length is now {}'.format(len(self.events)))

