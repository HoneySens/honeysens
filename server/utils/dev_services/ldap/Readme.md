## LDAP server
When this template is enabled in the server Makefile, an OpenLDAP server will be created within the honeysens development network. It's reachable on TCP ports 389 as well as 636 and supports unencrypted as well as encrypted traffic (via TLS or StartTLS). 

### Usage
To register this LDAP in the HoneySens UI, enter the following values in system settings and activate LDAP usage:
* **Server**: `ldap`
* **Port**: `389` or `636`
* **Encryption**: None or StartTLS for port 389, TLS for 636
* **Template**: `cn=%s,ou=users,dc=example,dc=org`

To test the LDAP functionality, a new user with LDAP as authentication backend needs to be created. The only user known to this LDAP instance is `ldapuser`, which should also be used as login for the newly created account. Afterwards, log out of the current session and authenticate as `ldapuser` with password `honeysens`.
