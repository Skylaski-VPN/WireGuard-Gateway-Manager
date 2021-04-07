# Setting Up DigitalOcean for WireGuard Gateway Manager

WireGuard Gateway Manager supports deploying WireGuard gateways and DNS servers to DigitalOcean.

In order for this to work, WireGuard Gateway Manager needs 4 things already setup in DigitalOcean.
1. DigitalOcean API Key with Read/Write access.
2. SSH Keys provisioned in DigitalOcean and accessible.
3. At least 1 Gateway VM Image aptly named so WireGuard Gateway Manager can see it.
4. At least 1 DNS VM Image aptly named so WireGuard Gateway Manager can see it.

This document will walk you through the steps of getting all 4 setup. 

## DigitalOcean API Key


