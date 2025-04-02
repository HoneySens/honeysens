## Proxy server
When this service is enabled in the server Makefile, a proxy server based on [tinyproxy](https://tinyproxy.github.io/) will be created within the HoneySens development network to carry out proxy-related tests. It's reachable on TCP port 8888 and doesn't require authentication. Moreover, if `EAPOL_ENABLED` is set to `true` in `docker-compose.yml`, the proxy container will also set up [hostapd](https://w1.fi/hostapd/), which then acts as an EAP authenticator. This way EAPOL support can be verified using just the development network. Additionally, the proxy will reject all requests as long as there wasn't at least one successful EAPOL authentication attempt (to simulate real-world behaviour).

### Usage
To set up the proxy server within the development environment,
* set `DEV_ENV_PROXY` in the server's Makefile to `yes`
* make sure the macvlan-based `proxy` network is available (NOT commented out) in the server's main `docker-compose-dev.yml`. The macvlan type is required for that network so that 802.1x EAP messages are forwarded between containers. A standard Linux bridge won't do that.
* make sure that the `proxy` network is also available (NOT commented out) for the dev sensor in its respective `docker-compose-dev.yml`. Comment out the `server` network within the same file (in both `networks` blocks) to ensure that there's no way for the sensor to contact the server but through the proxy container. Moreover, verify that `network_mode: host` is commented out to not interfere with the host's network stack. If EAPOL is to be used one should also uncomment the environment variable `EAPOL_IFACE=eth1` which forces the EAPOL module to use the `eth1` interface for authentication (which then will be the proxy-facing interface).

When the server is up and running, configure a sensor to use the proxy `proxy` on port `8888` to force traffic through the proxy. For EAPOL, all available authentication modes are support by the authenticator. The credentials are:
* **MD5**: `user1`:`password`
* **PEAP** and **TTLS**: `user2`:`password`
* **TLS**: `user2`, the required certificates and keys can be found within the `ca/` directory after the proxy container was started successfully