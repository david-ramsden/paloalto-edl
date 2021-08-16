# Palo Alto External Dynamic Lists for Various Services

## Introduction
This provides a number of External Dynamic Lists (EDLs) to be used by a Palo Alto firewall. The following services are supported:

* Microsoft 365.
* AWS.
* Zscaler.
* Polycom RealConnect.

The script will use a combination of public APIs and DNS queries to return a list of IP addresses for use in an EDL.

## Requirements
* PHP 7 (will probably work with PHP 5).
* cURL module for PHP.
* Apache 2 with mod_rewrite.
* Make outbound web requests (direct or via a proxy).
* Resolve external FQDNs.

## Installation
1. Clone the repository and move somewhere within the web server's document root.
2. To have requests cached for 24 hours, make the `cache` directory writeable by the web server (e.g. `chown www-data cache && chmod 755 cache`).
3. If you need to use a proxy to connect to external resources from the web server, edit `functions.inc.php`, find the `CURLOPT_PROXY` and `CURLOPT_PROXYPORT` options, uncomment them and set them appropriately.

### Ubuntu 21.04
These instructions will work with a vanilla install of Ubuntu 21.04 Server:
1. Install packages: `apt install apache2 libapache2-mod-php php-curl`
2. Enable Apache and PHP modules: `a2enmod rewrite php7.4 ; phpenmod curl`
3. Edit `/etc/apache2/sites-enabled/000-default.conf` and add to the VirtualHost stanza:
````
<Directory "/var/www/html">
  AllowOverride all
</Directory>
````
4. Restart Apache: `systemctl restart apache2`
5. Clone repository in to document root: `cd /var/www/html && git clone https://github.com/david-ramsden/paloalto-edl.git`
6. Enable caching and set proxy, if required (see Installation note #2 and #3 above).

## Updates
Pull the latest code from the repository using `git pull`. This will pull in any new services that get added.

## Usage
Create an External Dynamic List object on the firewall, where the source URL is: `http://your.server/paloalto-edl/<vendor>/<service>`

![Example EDL configuration screenshot](https://github.com/david-ramsden/paloalto-edl/blob/main/doc/resources/pa-edl-screenshot.png?raw=true "Example EDL configuration screenshot")

Note: With PAN-OS 8.1, a source URL using HTTPS was problematic. This was fixed in PAN-OS 9.0 and above.

### Vendors and Services
Vendor    | Service Required              | Services                                    | Optional Parameters                         |
----------|-------------------------------|---------------------------------------------|---------------------------------------------|
microsoft | No (will return all services) | `common`, `exchange`, `sharepoint`, `skype` |                                             |
aws       | No (will return all services) | Refer to [services](https://docs.aws.amazon.com/general/latest/gr/aws-ip-ranges.html#aws-ip-syntax) syntax. | `region=<region>` (refer to [region](https://docs.aws.amazon.com/general/latest/gr/aws-ip-ranges.html#aws-ip-syntax) syntax|
zscaler   | Yes                           | `cenr`, `pac`, `hub`                        | `zscloud=<cloud>` (defaults to zscloud.net) |
polycom   | No (defaults to `global`)     | `global`, `teams`, `sfb`                    |                                             |

#### Examples
* `/paloalto-edl/microsoft` will return all IPs and networks for Microsoft 365 services.
* `/paloalto-edl/microsoft/exchange` will return IPs and networks for Microsoft 365 Exchange Online service.
* `/paloalto-edl/aws/ec2` will return all IPs and networks for AWS EC2 globally.
* `/paloalto-edl/aws/s3?region=eu-west-3` will return all IPs and networks for AWS S3 in the eu-west-3 region.
* `/paloalto-edl/zscaler/hub` will return IPs and networks for Zscaler (zscloud.net) Hub IPs.
* `/paloalto-edl/zscaler/cenr?zscloud=zscaler.net` will return IPs and networks for Zscaler (zscaler.net) CENR IPs.
* `/paloalto-edl/polycom/teams` will return IPs used for outbound calls to Polycom RealConnect service for Microsoft Teams.

#### Notes
* Requests for `microsoft` will only return IPv4 addresses. IPv6 is not requested but this can be changed in the code if required.
* Requests for `aws` will only return IPv4 addresses.

#### References
* [Microsoft 365](https://docs.microsoft.com/en-us/microsoft-365/enterprise/urls-and-ip-address-ranges?view=o365-worldwide)
* [AWS](https://docs.aws.amazon.com/general/latest/gr/aws-ip-ranges.html)
* [Zscaler](https://config.zscaler.com/)
* [Polycom RealConnect](https://rc-docs.plcm.vc/docs/prerequisites#dns-hostnames)

#### Adding New Vendors and Services
If you would like to add new vendors and services, please submit a Pull Request with the required code modifications or submit an Issue to make a request.
