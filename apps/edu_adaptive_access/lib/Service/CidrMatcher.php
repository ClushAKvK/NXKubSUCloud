<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Service;

class CidrMatcher {
    public function matchesAny(?string $ip, array $cidrs): bool {
        if ($ip === null || trim($ip) === '') {
            return false;
        }

        foreach ($cidrs as $cidr) {
            $cidr = trim($cidr);
            if ($cidr === '') {
                continue;
            }
            if ($this->matches($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    public function matches(string $ip, string $cidr): bool {
        if (strpos($cidr, '/') === false) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = (int)$mask;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskBits = -1 << (32 - $mask);
        $subnetLong &= $maskBits;

        return ($ipLong & $maskBits) === $subnetLong;
    }
}