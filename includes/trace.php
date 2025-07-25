<?php
class SecureLinkTracer {
    
    private $allowedDomains = [];
    private $blockedDomains = ['localhost', '127.0.0.1', '0.0.0.0'];
    
    private function isUrlSafe($url) {
        $parsed = parse_url($url);
        
        if (!$parsed || !isset($parsed['host'])) {
            return ['safe' => false, 'reason' => 'Geçersiz URL formatı'];
        }

        $host = strtolower($parsed['host']);
        $scheme = strtolower($parsed['scheme'] ?? '');

        if (!in_array($scheme, ['http', 'https'])) {
            return ['safe' => false, 'reason' => 'Desteklenmeyen protokol'];
        }

        if (in_array($host, $this->blockedDomains)) {
            return ['safe' => false, 'reason' => 'Engellenen domain'];
        }

        if (!empty($this->allowedDomains) && !in_array($host, $this->allowedDomains)) {
            return ['safe' => false, 'reason' => 'İzin verilmeyen domain'];
        }

        $ipCheck = $this->checkHostIPs($host);
        if (!$ipCheck['safe']) {
            return $ipCheck;
        }

        return ['safe' => true, 'reason' => 'Güvenli URL'];
    }

    private function checkHostIPs($host) {
        $ipv4_addresses = gethostbynamel($host);
        if ($ipv4_addresses !== false) {
            foreach ($ipv4_addresses as $ip) {
                if (!$this->isIpSafe($ip, 'ipv4')) {
                    return ['safe' => false, 'reason' => "Güvenli olmayan IPv4 adresi: $ip"];
                }
            }
        }

        $dns_records = dns_get_record($host, DNS_AAAA);
        if ($dns_records !== false) {
            foreach ($dns_records as $record) {
                if (isset($record['ipv6']) && !$this->isIpSafe($record['ipv6'], 'ipv6')) {
                    return ['safe' => false, 'reason' => "Güvenli olmayan IPv6 adresi: {$record['ipv6']}"];
                }
            }
        }

        return ['safe' => true, 'reason' => 'Tüm IP adresleri güvenli'];
    }

    private function isIpSafe($ip, $version) {
        if ($version === 'ipv4') {
            return filter_var($ip, FILTER_VALIDATE_IP, 
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        } else {
            return filter_var($ip, FILTER_VALIDATE_IP, 
                FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }
    }

    private function setCurlSecurityOptions($ch, $url) {
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'UGotHere Secure Trace Bot/2.0',
            
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            
            CURLOPT_DOH_URL => 'https://1.1.1.1/dns-query',
        ]);
    }

    private function redirectCallback($ch, $redirectUrl) {
        $safetyCheck = $this->isUrlSafe($redirectUrl);
        if (!$safetyCheck['safe']) {
            return -1;
        }
        return 0;
    }

    public function traceLink($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'error' => 'Geçersiz URL formatı',
                'original_url' => $url
            ];
        }

        $safetyCheck = $this->isUrlSafe($url);
        if (!$safetyCheck['safe']) {
            return [
                'success' => false,
                'error' => 'Güvenlik ihlali: ' . $safetyCheck['reason'],
                'original_url' => $url
            ];
        }

        $ch = curl_init();
        $this->setCurlSecurityOptions($ch, $url);

        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            curl_setopt($ch, CURLOPT_REDIR_CALLBACK, [$this, 'redirectCallback']);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $redirectCount = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'success' => false,
                'error' => 'cURL hatası: ' . $error,
                'original_url' => $url
            ];
        }

        curl_close($ch);

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => "HTTP Hatası: $httpCode",
                'original_url' => $url,
                'http_code' => $httpCode
            ];
        }

        if ($finalUrl !== $url) {
            $finalSafetyCheck = $this->isUrlSafe($finalUrl);
            if (!$finalSafetyCheck['safe']) {
                return [
                    'success' => false,
                    'error' => 'Son URL güvenlik ihlali: ' . $finalSafetyCheck['reason'],
                    'original_url' => $url
                ];
            }
        }

        return [
            'success' => true,
            'original_url' => $url,
            'final_url' => $finalUrl,
            'redirected' => ($finalUrl !== $url),
            'redirect_count' => $redirectCount,
            'http_code' => $httpCode,
            'total_time' => $totalTime,
            'message' => $finalUrl !== $url 
                ? "URL başarıyla izlendi ($redirectCount yönlendirme)" 
                : "URL zaten gerçek hedefidir"
        ];
    }

    public function setAllowedDomains(array $domains) {
        $this->allowedDomains = array_map('strtolower', $domains);
    }

    public function setBlockedDomains(array $domains) {
        $this->blockedDomains = array_map('strtolower', $domains);
    }
}

?>