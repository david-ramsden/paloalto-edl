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
 * Fetches a URL using a proxy.
 * Contents are cached on disk and subsequent requests for the same URL will be served from cache.
 * Cache expires after 24 hours.
 *
 * @param string $url	The URL to fetch.
 * @return text		The content of the URL.
 */
function fetch_url($url) {
	// Cache file is SHA1 hash of URL.
	$cache_file = 'cache/' . sha1($url);

	// If cache file exists...
	if (file_exists($cache_file)) {
		// ...and cache file is less than 24 hours old,
		// return the contents of the cache file.
		if (time() - filemtime($cache_file) <= (24 * 60 * 60)) {
			return file_get_contents($cache_file);
		}
	}

	// Use cURL to fetch URL.
        $ch = curl_init();

	// Use a proxy.
	// Ignore certificate errors.
	// Follow redirects.
        curl_setopt($ch, CURLOPT_PROXY, 'http://gateway.zscloud.net');
		curl_setopt($ch, CURLOPT_PROXYPORT, '80');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        $content = curl_exec($ch);

	// On an error, return HTTP Status 404.
        if (curl_errno($ch) || !strlen($content)) {
		if (curl_errno($ch)) {
			error_log("cURL request failed: " . curl_error($ch));
		} elseif (!strlen($content)) {
			error_log("cURL request failed: no content returned after request.");
		}
                curl_close($ch);
                http_response_code(404);
                exit();
        }

        curl_close($ch);

	@file_put_contents($cache_file, $content, LOCK_EX);

        return $content;
}

/**
 * Generates a valid GUID.
 *
 * @param bool $rnd	If not specified/not true, return a static GUID otherwise generate a random GUID.
 * @return text		The GUID.
 */
function generate_guid($rnd = false) {
	if (empty($rnd)) {
		return 'b3911430-1eeb-4685-8692-3626b1d44b8f';
	}

        return strtolower(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)));
}

/**
 * Validates the given string is a valid IPv4 address.
 * Removes any whitespace.
 *
 * @param string $ip	The IPv4 address to validate.
 * @return text/bool	Returns the IPv4 address, less any whitespace, if valid or if not returns false.
 */
function validate_ipv4($ip = "") {
	$ip = preg_replace('/[ ]/', '', $ip);

	if (empty($ip) || !preg_match('/^\d+\.\d+\.\d+\.\d+/', $ip)) {
		return false;
	}

	return $ip;
}
?>
