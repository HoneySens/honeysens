#!/usr/bin/env python3
import argparse
from datetime import datetime
from ftplib import FTP
import random
import requests
import secrets
import socket
import string
import sys
import time


def log(data: str) -> None:
    """Print data to console prepended with current timestamp"""
    print(f"{str(datetime.now())} - {data}")


def ev_generic_tcp(host: str) -> None:
    """TCP connection attempt on a random port"""
    port = random.randint(1, 2**16 - 1)
    log(f"TCP packet on port {port} to {host}")
    s = socket.socket()
    s.connect((host, port))
    s.send(secrets.token_bytes(random.randint(1, 256)))
    s.close()


def ev_generic_udp(host: str) -> None:
    """UDP packet sent to a random port"""
    port = (host, random.randint(1, 2**16 - 1))
    log(f"UDP packet on port {port} to {host}")
    s = socket.socket(type=socket.SOCK_DGRAM)
    s.sendto(secrets.token_bytes(random.randint(1, 256)), port)
    s.close()


def ev_ftp(host: str) -> None:
    """FTP login attempt"""
    log(f"FTP login to ftp://{host}")
    passwd = "".join(random.choices(string.ascii_lowercase + string.digits, k=10))
    try:
        FTP(host, user="root", passwd=passwd)
    except Exception:
        pass


def ev_https_auth(host: str) -> None:
    """HTTPS basic auth attempt"""
    log(f"HTTPS basic auth at https://{host}")
    passwd = "".join(random.choices(string.ascii_lowercase + string.digits, k=10))
    requests.get(f"https://{host}/", verify=False, auth=("root", passwd))


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--ip", "-i", type=str, action="append", help="Sensor IP(s)")
    parser.add_argument("--sleep-min", type=int, default=5, help="Minimal event interval (minutes), default 5 min")
    parser.add_argument("--sleep-max", type=int, default=15, help="Maximal event interval (minutes), default 15 min")
    args = parser.parse_args()
    if args.ip is None:
        print("At least one target IP via -i or --ip required")
        sys.exit(1)
    events = [ev_generic_tcp, ev_generic_udp, ev_ftp, ev_https_auth]
    print(f"Targets: {args.ip}")
    print(f"Interval: {args.sleep_min} to {args.sleep_max} minutes")
    while True:
        time.sleep(random.randint(args.sleep_min * 60, args.sleep_max * 60))
        target_host = args.ip[random.randint(0, len(args.ip) - 1)]
        try:
            events[random.randint(0, len(events) - 1)](target_host)
        except Exception:
            continue
