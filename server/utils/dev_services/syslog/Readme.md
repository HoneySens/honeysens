## Syslog server
When this service is enabled in the server Makefile, a syslog server will be made available within the HoneySens development network. It's reachable on port 514 for both TCP and UDP (TLS is unsupported) and can be utilized to test event forwarding (i.e. for SIEM systems).

### Usage
To register this syslog server in the HoneySens UI as event forwarding target, enter the following values in system settings and activate event forwarding:
* **Server**: `syslog`
* **Port**: `514`
* **Protocol, Facility, Priority**: any combination of the available options
