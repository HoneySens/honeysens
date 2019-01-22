#!/usr/bin/env python2

# Not-so-passive service that collects information about received IP packets that aren't
# directed at one of the local listening services and reports them to the HoneySens server.
# It answers packets that are:
# - directed at the local host
# - not directed at one of the local services
# - not received from or sent to the HoneySens server

import base64
import netifaces
import os
import random
import signal
import subprocess
import sys
import threading
import time
import zmq
from scapy.all import *

# Constants
SERVICE_ID = 0            # Internal HoneySens ID to identify data from this service
SEND_WAIT_TIMEOUT = 6     # Only send an event if it didn't receive new packets for x seconds
FLOODER_CAP = 25          # Packet threshold to classify a remote host as a flooder and the event as a scan
FLOODER_TIMEOUT = 60      # Seconds to wait until packets from a flooder are accepted again
IFACE = 'eth0'           # Interface to listen on

# Globals
seq = random.randint(0, 10000)                          # sequence number for generated TCP packets
iptables_exec = '/sbin/iptables'                      # path to iptables binary
events = {}                                             # Currently "active" events, e.g. TCP connections including all related packages
flooders = {}                                           # Remote hosts that have been flooding this sensor and are now ignored
collector_host = None                                   # IP to send events to via ZMQ
collector_port = None                                   # Port the collector service is listening on
localhost = None                                        # Destination IP of incoming packets
safe_hosts = []                                         # List of trusted hosts, which are ignored during packet inspection
worker_thread = None                                    # Reference to the event worker thread
zmq_context = zmq.Context()                             # ZMQ context used to send events to the collector

print('HoneySens recon')
localhost = netifaces.ifaddresses(IFACE)[2][0]['addr']
print('  Responding to packets for {}'.format(localhost))
if 'COLLECTOR_HOST' not in os.environ or 'COLLECTOR_PORT' not in os.environ:
  print('Error: No HoneySens collector specified')
  sys.exit(1)
else:
  collector_host = os.environ['COLLECTOR_HOST']
  collector_port = os.environ['COLLECTOR_PORT']
  safe_hosts.append(collector_host)
  print('  HoneySens collector available at tcp://{}:{}'.format(collector_host, collector_port))
for host in safe_hosts:
  print('  Whitelisted: {}'.format(host))

# Thread that does the collection, classification and notification of events
def worker():
  global worker_thread
  worker_thread = threading.Timer(1, worker)
  worker_thread.setDaemon(True)
  worker_thread.start()
  send_canidates = []
  # Process the event queue
  for src, e in events.iteritems():
    # Cap max number of packets per event at FLOODER_CAP, classify the event as a scan and put the remote host into the flooder list
    if len(e['packets']) >= FLOODER_CAP and src not in flooders:
      print('Too many packets from {}, classifying as scan and ignoring for {} seconds'.format(src, FLOODER_TIMEOUT))
      e['summary'] = 'Scan'
      flooders[src] = int(time.time())
    # Put all events that didn't receive packages within the last SEND_WAIT_TIMEOUT seconds into the 'send' queue
    if int(time.time()) - e['packets'][-1]['timestamp'] >= SEND_WAIT_TIMEOUT:
      send_canidates.append(e)
  # Process the send queue
  if len(send_canidates) > 0:
    print('Sending {} collected events to the server'.format(len(send_canidates)))
    for c in send_canidates:
      events.pop(c['source'])
      socket = zmq_context.socket(zmq.REQ)
      socket.connect('tcp://{}:{}'.format(collector_host, collector_port))
      socket.send_json(c)
      socket.recv()
      socket.close()
  # Process the flooder queue
  for src, f in list(flooders.iteritems()):
    # Remove remote host blocks after FLOODER_TIMEOUT seconds
    if (int(time.time()) - f) >= FLOODER_TIMEOUT:
      print('Host {} is not ignored anymore'.format(src))
      del flooders[src]

def get_event(src_ip):
  # Look up an existing event or create a new one if necessary
  if src_ip in events:
    incident_data = events[src_ip]
  else:
    incident_data = {'packets': [], 'details': [], 'service': SERVICE_ID, 'source': src_ip, 'summary': 'Einzelverbindung'}
  return incident_data

# Packet event handler, executed for each received packet
def packet_handler(p):
  global seq
  incident_detected = False
  incident_data = {}
  # Only act on IP packets that are
  # - sent directly to this host (no broadcasts or multicasts)
  # - not received from any safe hosts
  # - not in the list of ignored flooders
  if IP in p and p[IP].dst == localhost and p[IP].src not in safe_hosts and p[IP].src not in flooders:
    src_ip = p[IP].src
    # TCP packets
    if TCP in p:
      incident_detected = True
      incident_data = get_event(src_ip)
      packet = {'headers': [{'flags': p[TCP].flags}], 'protocol': 1, 'port': p[TCP].dport, 'timestamp': int(time.time()), 'payload': None}
      if p[TCP].flags == 0x02: # SYN
        send(IP(src=p[IP].dst, dst=p[IP].src)/TCP(flags="SA", sport=p[TCP].dport, dport=p[TCP].sport, ack=p[TCP].seq+1, seq=seq))
        seq = seq + 1
      elif p[TCP].flags == 0x011: # FIN-ACK
        # TODO state machine -> check for handshake
        send(IP(src=p[IP].dst, dst=p[IP].src)/TCP(flags="FA", sport=p[TCP].dport, dport=p[TCP].sport, ack=p[TCP].seq+1, seq=seq))
      elif str(p[TCP].payload) != '':
        #if p[TCP].flags in [0x10, 0x18]: # ACK or PSH-ACK
          #send(IP(src=p[IP].dst, dst=p[IP].src)/TCP(flags="A", sport=p[TCP].dport, dport=p[TCP].sport, ack=p[TCP].seq+len(p[TCP].load), seq=seq))
        # Save payload and reset connection
        if hasattr(p[TCP], 'payload') and hasattr(p[TCP], 'load'):
            packet['payload'] = base64.b64encode(p[TCP].payload.load)
        send(IP(src=p[IP].dst, dst=src_ip)/TCP(flags="R", sport=p[TCP].dport, dport=p[TCP].sport, ack=p[TCP].seq+len(p[TCP].load), seq=seq))
        #print('TCP payload: {}'.format(p[TCP].payload))
      incident_data['packets'].append(packet)
    # UDP packets
    elif UDP in p:
      incident_detected = True
      incident_data = get_event(src_ip)
      payload = None
      if hasattr(p[UDP], 'payload') and hasattr(p[UDP].payload, 'load'):
          payload = base64.b64encode(p[UDP].payload.load)
      packet = {'headers': [], 'protocol': 2, 'port': p[UDP].dport, 'timestamp': int(time.time()), 'payload': payload}
      #print('UDP payload: {}'.format(p[UDP].payload.load))
      incident_data['packets'].append(packet)
  if incident_detected:
    # Add a new event if this packet belongs to an so far unknown remote host
    if src_ip not in events:
      incident_data['packets'].append(packet)
      incident_data['timestamp'] = int(time.time())
      events[src_ip] = incident_data

# Add netfilter rule to block all outgoing DROP packets
print('Setting up netfilter rules...')
if subprocess.call([iptables_exec, '-C', 'OUTPUT', '-p', 'tcp', '--tcp-flags', 'RST', 'RST', '-j', 'DROP'], stderr=subprocess.PIPE) == 1:
  subprocess.call([iptables_exec, '-A', 'OUTPUT', '-p', 'tcp', '--tcp-flags', 'RST', 'RST', '-j', 'DROP'])

# Register SIGTERM handler for clean shutdown
def sigtermhandler(signal, frame):
  print('Performing shutdown')
  subprocess.call([iptables_exec, '-D', 'OUTPUT', '-p', 'tcp', '--tcp-flags', 'RST', 'RST', '-j', 'DROP'])
  sys.exit(0)
signal.signal(signal.SIGTERM, sigtermhandler)
signal.signal(signal.SIGINT, sigtermhandler)

# Launch worker thread
worker()

sniff(iface=IFACE, prn=packet_handler, store=0)
