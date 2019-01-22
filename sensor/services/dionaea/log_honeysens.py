from datetime import datetime
import glob
import hashlib
import logging
import os
import time
import zmq

from dionaea import IHandlerLoader
from dionaea.core import ihandler, connection
from dionaea.exception import LoaderError

logger = logging.getLogger("log_honeysens")
logger.setLevel(logging.DEBUG)


class LogHoneySensHandlerLoader(IHandlerLoader):
    name = "log_honeysens"

    @classmethod
    def start(cls, config=None):
        try:
            return LogHoneySensHandler("*", config=config)
        except LoaderError as e:
            logger.error(e.msg, *e.args)


class LogHoneySensHandler(ihandler):
    def __init__(self, path, config=None):
        logger.debug("%s ready!", self.__class__.__name__)
        ihandler.__init__(self, path)
        self.path = path
        self._config = config
        self._connection_ids = {}
        self._disabled = False
        self._collector_host = None
        self._collector_port = None
        self._zmq_context = zmq.Context()

        if 'COLLECTOR_HOST' not in os.environ or 'COLLECTOR_PORT' not in os.environ:
            print('Error: No HoneySens collector specified, logging module disabled')
            self._disabled = True
        else:
            self._collector_host = os.environ['COLLECTOR_HOST']
            self._collector_port = os.environ['COLLECTOR_PORT']
            print('HoneySens collector available at tcp://{}:{}'.format(self._collector_host, self._collector_port))

    def submit(self, data):
        messages = []
        event = {'timestamp': data['timestamp'], 'source': data['source'], 'service': 1, 'summary': data['protocol'], 'details': data['messages'], 'packets': []}
        # Collector connection
        socket = self._zmq_context.socket(zmq.REQ)
        # TODO Error handling
        socket.connect("tcp://{}:{}".format(self._collector_host, self._collector_port))
        socket.send_json(event)
        # TODO This BLOCKS in case there is no response (e.g. error on collector)
        socket.recv()
        # Cleanup
        socket.close()

    def handle_incident(self, icd):
        #if self._disabled:
            #return

        icd.dump()
        if icd.origin == "dionaea.connection.link":
            if icd.parent not in self._connection_ids:
                # Don't link connections if parent is not available
                # This should only happen if the parent is the listening server connection
                return

        idata = {}
        for k in icd.keys():
            n = k.decode("ASCII")
            v = getattr(icd, n)
            if isinstance(v, (int, float, str, list, tuple, dict)) or v is None:
                logger.debug("Add '%s' to icd data", n)
                idata[n] = v
            elif isinstance(v, set):
                # a set() is not JSON serializable, so we use lists instead
                logger.debug("Add '%s' to icd data", n)
                idata[n] = list(v)
            elif isinstance(v, bytes):
                logger.debug("Decode and add '%s' to icd data", n)
                idata[n] = v.decode(encoding="utf-8", errors="replace")
            elif isinstance(v, connection):
                k = k.decode("ASCII")
                if k == "con":
                    k = "connection"

                tmp_data = {
                    "protocol": v.protocol,
                    "transport": v.transport,
                    # "type": v.connection_type,
                    "local_ip": v.local.host,
                    "local_port": v.local.port,
                    "remote_hostname": v.remote.hostname,
                    "remote_ip": v.remote.host,
                    "remote_port": v.remote.port
                }
                conn_data = self._connection_ids.get(v)
                if conn_data is None:
                    raw_id = "%r_%d_%r" % (
                        tmp_data,
                        id(v),
                        datetime.utcnow()
                    )

                    conn_id = hashlib.sha256(raw_id.encode("ASCII")).hexdigest()
                    conn_data = {'id': conn_id, 'messages': [{'timestamp': int(time.time()), 'data': 'Protocol: {}/{}, Remote Port: {}'.format(tmp_data['protocol'], tmp_data['transport'], tmp_data['remote_port']), 'type': 1}], 'source': tmp_data['remote_ip'], 'timestamp': int(time.time()), 'protocol': tmp_data['protocol']}
                    self._connection_ids[v] = conn_data
                tmp_data["id"] = conn_data.get('id')
                idata[k] = tmp_data
            else:
                logger.warning("Incident '%s' with unknown data type '%s' for key '%s'", icd.origin, type(v), k)

        data = {
            "timestamp": datetime.utcnow().isoformat(),
            "name": "dionaea",
            "origin": icd.origin,
            "data": idata
        }

        origin = data['origin']
        icd_desc = 'Event: {}'.format(origin)
        if origin == 'dionaea.connection.tcp.listen':
            icd_desc = 'Listening TCP connection on {}:{}'.format(icd.con.remote.host, icd.con.remote.port)
        elif origin == 'dionaea.connection.tls.listen':
            icd_desc = 'Listening TLS connection on {}:{}'.format(icd.con.remote.host, icd.con.remote.port)
        elif origin == 'dionaea.connection.tcp.connect':
            icd_desc = 'Establishing TCP connection to {}/{}:{}'.format(icd.con.remote.host, icd.con.remote.hostname, icd.con.remote.port)
        elif origin == 'dionaea.connection.tls.connect':
            icd_desc = 'Establishing TLS connection to {}/{}:{}'.format(icd.con.remote.host, icd.con.remote.hostname, icd.con.remote.port)
        elif origin == 'dionaea.connection.udp.connect':
            icd_desc = 'Establishing UDP connection to {}/{}:{}'.format(icd.con.remote.host, icd.con.remote.hostname, icd.con.remote.port)
        elif origin == 'dionaea.connection.tcp.accept':
            icd_desc = 'Accepted TCP connection from {}:{} to port {}'.format(icd.con.remote.host, icd.con.remote.port, icd.con.local.port)
        elif origin == 'dionaea.connection.tls.accept':
            icd_desc = 'Accepted TLS connection from {}:{} to port {}'.format(icd.con.remote.host, icd.con.remote.port, icd.con.local.port)
        elif origin == 'dionaea.connection.tcp.reject':
            icd_desc = 'Rejected TCP connection from {}:{} to port {}'.format(icd.con.remote.host, icd.con.remote.port, icd.con.local.port)
        elif origin == 'dionaea.connection.tcp.pending':
            icd_desc = 'Pending TCP connection from {}:{} to port {}'.format(icd.con.remote.host, icd.con.remote.port, icd.con.local.port)
        elif origin == 'dionaea.connection.free':
            icd_desc = 'Connection closed'
        elif origin == 'dionaea.module.emu.profile':
            icd_desc = 'Libemu profile: {}'.format(icd.profile)
        elif origin == 'dionaea.download.offer':
            icd_desc = 'Download offer: {}'.format(icd.url)
        elif origin == 'dionaea.download.complete':
            icd_desc = 'Download completed'
        elif origin == 'dionaea.download.complete.hash':
            icd_desc = 'Downloaded {} (MD5: {})'.format(icd.url, icd.md5hash)
        elif origin == 'dionaea.service.shell.listen':
            icd_desc = 'Listening shell at bindshell://{}'.format(str(icd.port))
        elif origin == 'dionaea.service.shell.connect':
            icd_desc = 'Shell connect to connectbackshell://{}:{}'.format(str(icd.host), str(icd.port))
        elif origin == 'dionaea.modules.python.smb.dcerpc.request':
            icd_desc = 'DCE/RPC request operation {} (UUID {})'.format(icd.opnum, icd.uuid)
        elif origin == 'dionaea.modules.python.smb.dcerpc.bind':
            icd_desc = 'DCE/RPC bind: {} (UUID {})'.format(icd.transfersyntax, icd.uuid)

        self._connection_ids.get(v).get('messages').append({'timestamp': int(time.time()), 'data': icd_desc, 'type': 1})

        if icd.origin == "dionaea.connection.free":
            con = icd.con
            if con in self._connection_ids:
                logger.debug("Remove connection ID '%s' from list.", self._connection_ids.get(con).get('id'))
                self.submit(self._connection_ids.get(v))
                del self._connection_ids[con]
                # Clean up locally saved binaries to not waste any space
                for f in glob.glob('/opt/dionaea/var/lib/dionaea/binaries/*'):
                    os.remove(f)
