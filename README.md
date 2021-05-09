# Palo Alto External Dynamic Lists for Various Services

## Introduction
This provides a number of External Dynamic Lists (EDLs) to be used by a Palo Alto firewall. The following services are supported:

* Microsoft 365.
* Zscaler.
* Polycom RealConnect.

The script will use a combination of public APIs and DNS queries to return a list of IP addresses for use in an EDL.

## Requirements
* PHP 7 (will probably work with PHP 5).
* Apache 2 with mod_rewrite.

## Installation
1. Clone the repository and move somewhere within the web server's document root.
2. Make the `cache` directory writeable by the web server (e.g. `chown www-data cache && chmod 755 cache`).
3. If you need to use a proxy to connect to external resources from the web server, edit `functions.inc.php` and set the `CURLOPT_PROXY` and `CURLOPT_PROXYPORT` options, **otherwise comment these lines out**.

## Usage
Create an External Dynamic List object on the firewall, where the source URL is: `http://your.server/edl/<vendor>/<service>`

![Example EDL configuration screenshot](https://github.com/david-ramsden/paloalto-edl/blob/main/doc/resources/pa-edl-screenshot.png?raw=true "Example EDL configuration screenshot")

Note: With PAN-OS 8.1, a source URL using HTTPS was problematic. This was fixed in PAN-OS 9.0 and above.

### Vendors and Services
Vendor    | Service Required              | Services                                    | Optional Parameters                         |
----------|-------------------------------|---------------------------------------------|---------------------------------------------|
microsoft | No (will return all services) | `common`, `exchange`, `sharepoint`, `skype` |                                             |
zscaler   | Yes                           | `cenr`, `pac`, `hub`                        | `zscloud=<cloud>` (defaults to zscloud.net) |
polycom   | No (defaults to `global`)     | `global`, `teams`, `sfb`                    |                                             |

#### Examples
* `/edl/microsoft` would return all IPs and networks for Microsoft 365 services.
* `/edl/microsoft/exchange` would return IPs and networks for Microsoft 365 Exchange Online service.
* `/edl/zscaler/hub` would return IPs and networks for Zscaler (zscloud.net) Hub IPs.
* `/edl/zscaler/cenr/zscloud=zscaler.net` would return IPs and networks for Zscaler (zscaler.net) CENR IPs.
* `/edl/polycom/teams` would return IPs used for outbound calls to Polycom RealConnect service for Microsoft Teams.

#### Notes
* Requests for `microsoft` will only return IPv4 addresses. IPv6 is not requested but this can be changed in the code if required.

#### References
* [Microsoft 365](https://docs.microsoft.com/en-us/microsoft-365/enterprise/urls-and-ip-address-ranges?view=o365-worldwide)
* [Zscaler](https://config.zscaler.com/)
* [Polycom RealConnect](https://rc-docs.plcm.vc/docs/prerequisites#dns-hostnames)

#### Adding New Vendors and Services
If you would like to add new vendors and services, please submit a Pull Request with the required code modifications or submit an Issue to make a request.
