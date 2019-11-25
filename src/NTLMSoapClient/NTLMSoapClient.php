<?php

/*
 * Copyright (c) 2008 Invest-In-France Agency http://www.invest-in-france.org
 *
 * Author : Thomas Rabaix
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

namespace Drupal\PjatkForm\NTLMSoapClient;

use \Exception;
use \SoapClient;

class NTLMSoapClient extends SoapClient {
	private $options = [];

	/**
	 * @param mixed $url WSDL url (eg: http://dominio.tld/webservice.aspx?wsdl)
	 * @param array $data Array to be used to create an instance of SoapClient. Can take the same parameters as the native class
	 * @throws Exception
	 */
	public function __construct($url, $data) {
		$this->options = $data;

		if (empty($data['ntlm_username']) && empty($data['ntlm_password'])) {
			parent::__construct($url, $data);
		} else {
			$this->use_ntlm = true;
			NTLMStream::$user = $data['ntlm_username'];
			NTLMStream::$pass = $data['ntlm_password'];

			// Remove HTTP stream registry
			stream_wrapper_unregister('http');

			// Register our defined NTLM stream
			if (!stream_wrapper_register('http', 'Drupal\\PjatkForm\\NTLMSoapClient\\NTLMStream')) {
				throw new Exception("Unable to register HTTP Handler");
			}

			// Create an instance of SoapClient
			parent::__construct($url, $data);

			// Since our instance is using the defined NTLM stream,
			// you now need to reset the stream wrapper to use the default HTTP.
			// This way you're not messing with the rest of your application or dependencies.
			stream_wrapper_restore('http');
		}
	}

	/**
	 * Create a cURL request and return the method's response
	 * @param string $request
	 * @param string $location
	 * @param string $action
	 * @param int $version
	 * @param int $one_way
	 * @see SoapClient::__doRequest()
	 * @return mixed
	 */
	public function __doRequest($request, $location, $action, $version, $one_way = 0) {
		$this->__last_request = $request;

		$ch = curl_init($location);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Method: POST',
			'User-Agent: PHP-SOAP-CURL',
			'Content-Type: text/xml; charset=utf-8',
			'SOAPAction: "' . $action . '"',
		]);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		if (!empty($this->options['ntlm_username']) && !empty($this->options['ntlm_password'])) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
			curl_setopt($ch, CURLOPT_USERPWD, $this->options['ntlm_username'] . ':' . $this->options['ntlm_password']);
		}
		$response = curl_exec($ch);

		return $response;
	}
}
