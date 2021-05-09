<?php
/**
 * Copyright (c) 2021 David Ramsden
 *
 * This software is provided 'as-is', without any express or implied
 * warranty. In no event will the authors be held liable for any damages
 * arising from the use of this software.
 *
 * Permission is granted to anyone to use this software for any purpose,
 * including commercial applications, and to alter it and redistribute it
 * freely, subject to the following restrictions:
 *
 * 1. The origin of this software must not be misrepresented; you must not
 *   claim that you wrote the original software. If you use this software
 *   in a product, an acknowledgment in the product documentation would be
 *   appreciated but is not required.
 * 2. Altered source versions must be plainly marked as such, and must not be
 *   misrepresented as being the original software.
 * 3. This notice may not be removed or altered from any source distribution.
 */

/**
 * Will return:
 *  200 - No errors/issues.
 *  400 - Malformed request.
 *  503 - Error with external resource (either fetching or with the data it returned).
 *
 * Any errors (400 or 503) will be logged using error_log().
 *
 * Vendor: polycom
 *  Service: teams, sfb, global
 *  If no service is provided, "global" is the default.
 * Vendor: microsoft
 *  Service: exchange, sharepoint, lync, common
 *  If no service is provided, all services are returned.
 * Vendor: zscaler
 *  Service: pac, cenr, hub
 *  A service must be provided.
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);

include('functions.inc.php');

/**
 * Takes an array of IP addresses.
 * Removes any duplicates.
 * Ensures at least 1 IP address is present.
 * Displays IPs, one per line.
 *
 * @param array $ips	An array of valid IP addresses.
 */
function display_ips($ips) {
	// Remove duplicates.
	$ips = array_unique($ips);

	// Ensure there is at least 1 IP.
	if (!count($ips)) {
		error_log("vendor = " . $_GET['vendor'] . ", service = $service: No IPs to return.");
		http_response_code(503);
		exit();
	}

	foreach ($ips as $ip) {
		echo "$ip\n";
	}
}

header('Content-type: text/plain');

switch ($_GET['vendor']) {
	case 'polycom':
		$service = !empty($_GET['service']) ? $_GET['service'] : 'global';

		// Get all DNS A records associated with the service.
		if (($results = dns_get_record('edge-' . $service . '.plcm.vc', DNS_A)) === false) {
			error_log("vendor = " . $_GET['vendor'] . ", service = $service: dns_get_record() failed.");
			http_response_code(503);
			exit();
		}

		// Validate each result.
		$ips = array();
		foreach ($results as $result) {
			if (($valid_ip = validate_ipv4($result['ip']))) {
				array_push($ips, $valid_ip);
			}
		}

		display_ips($ips);

		break;

	case 'microsoft':
		$service = !empty($_GET['service']) ? $_GET['service'] : '';

		// Fetch JSON.
		if (($json = json_decode(fetch_url('https://endpoints.office.com/endpoints/WorldWide?ClientRequestId=' . generate_guid() . '&NoIPv6=true&Instance=Worldwide'))) === null) {
			error_log("vendor = " . $_GET['vendor'] . ", service = $service: Fetching JSON failed.");
			http_response_code(503);
			exit();
		}

		$ips = array();
		foreach ($json as $element) {
			foreach ($element as $key => $val) {
				// Only interested in IPs.
				if ($key === 'ips') {
					// If a specific service has been requested and it matches the serviceArea.
					if (!empty($service) && preg_match('/' . $element->{'serviceArea'} . '/i', $service)) {
						$ips = array_merge($ips, $val);
					// Otherwise no service was requested so the serviceArea doesn't matter.
					} elseif (empty($service)) {
						$ips = array_merge($ips, $val);
					}
				}
			}
		}

		// Validate the IPs.
		foreach ($ips as $key => $val) {
			if (($valid_ip = validate_ipv4($val))) {
				$ips[$key] = $valid_ip;
			} else {
				// Remove element from array.
				unset($ips[$key]);
			}
		}

		display_ips($ips);

		break;

	case 'zscaler':
		$zscloud = !empty($_GET['zscloud']) ? $_GET['zscloud'] : 'zscloud.net';

		switch ($_GET['service']) {
			case 'pac':
				// Fetch JSON.
				if (($json = json_decode(fetch_url("https://api.config.zscaler.com/$zscloud/pac/json"))) === null) {
					error_log("vendor = " . $_GET['vendor'] . ", service = " . $_GET['service'] . ": Fetching JSON failed.");
					http_response_code(503);
					exit();
				}

				// Validate IPs.
				$ips = array();
				foreach ($json->{'ip'} as $ip) {
					if (($ip = validate_ipv4($ip))) {
						array_push($ips, $ip);
					}
				}

				display_ips($ips);

				break;

			case 'cenr':
				// Fetch JSON.
				if (($json = json_decode(fetch_url("https://api.config.zscaler.com/$zscloud/cenr/json"))) === null) {
					error_log("vendor = " . $_GET['vendor'] . ", service = " . $_GET['service'] . ": Fetching JSON failed.");
					http_response_code(503);
					exit();
				}

				$ips = array();

				foreach ($json->{$zscloud} as $continent => $cities) {
					foreach ($cities as $city => $datacentres) {
						foreach ($datacentres as $datacentre) {
							// Validate IP.
							if (($ip = validate_ipv4($datacentre->{'range'}))) {
								array_push($ips, $ip);
							}
						}
					}
				}

				display_ips($ips);

				break;

			case 'hub':
				// This could break. Zscaler do not provide an API for obtaining the Hub IPs.
				// This uses the JSON returned to a client's web browser when viewing the Firewall Config. Requirements page.
				// Because it's not an official API, it's subject to change.

				// Fetch JSON.
				if (($json = json_decode(fetch_url("https://api.config.zscaler.com/api/getdata/$zscloud/all/fcr"))) === null) {
					error_log("vendor = " . $_GET['vendor'] . ", service = " . $_GET['service'] . ": Fetching JSON failed.");
					http_response_code(503);
					exit();
				}

				$ips = array();

				// Hub IPs are currently at index 9.
				foreach ($json->{'data'}[9]->{'body'}->{'json'}->{'rows'}[0]->{'cols'} as $key => $val) {
					foreach ($val as $key => $val) {
						// This is the "Required" column of the Hub IPs.
						if ($key === 'required' && !empty($val)) {
							// Validate IP.
							if (($ip = validate_ipv4($val))) {
								array_push($ips, $ip);
							}
						}
					}
				}

				// FQDNs to resolve and include.
				$fqdns = array('mobile.zscloud.net', 'login.zscloud.net');
				foreach ($fqdns as $fqdn) {
					if (($results = dns_get_record($fqdn, DNS_A)) === false) {
						error_log("vendor = " . $_GET['vendor'] . ", service = $service: dns_get_record() failed.");
						continue;
					}

					foreach ($results as $result) {
						if (($valid_ip = validate_ipv4($result['ip']))) {
							array_push($ips, $valid_ip);
						}
					}
				}

				display_ips($ips);

				break;

			default:
				// Service requested is unknown.
				error_log("vendor = " . $_GET['vendor'] . ", service = " . $_GET['service'] . ": Service is not known.");
				http_response_code(400);
				exit();

				break;
		}

		break;

	default:
		// Vendor requested is unknown.
		error_log("vendor = " . $_GET['vendor'] . ", service = " . $_GET['service'] . ": Vendor is not known.");
		http_response_code(400);
		exit();

		break;
}
?>
