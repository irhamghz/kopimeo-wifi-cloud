<?php

require_once __DIR__ . '/routeros_api.class.php';

$message = '';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function isPrivateRouterIp($ip)
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $octets = array_map('intval', explode('.', $ip));

        return $octets[0] === 10
            || ($octets[0] === 172 && $octets[1] >= 16 && $octets[1] <= 31)
            || ($octets[0] === 192 && $octets[1] === 168);
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $packed = inet_pton($ip);
        return $packed !== false && (ord($packed[0]) & 0xFE) === 0xFC;
    }

    // Require an IP address; do not accept a public hostname here.
    return false;
}

function throwOnRouterTrap(array $response)
{
    foreach ($response as $row) {
        if (($row['_type'] ?? '') === '!trap') {
            throw new RuntimeException(
                'MikroTik error: ' . ($row['message'] ?? 'The router rejected the request.')
            );
        }
    }
}

if (isset($_POST['click_generate'])) {
    $host = trim((string) getenv('MIKROTIK_HOST'));
    $portValue = trim((string) getenv('MIKROTIK_PORT'));
    $username = (string) getenv('MIKROTIK_USERNAME');
    $password = (string) getenv('MIKROTIK_PASSWORD');
    $profile = trim((string) getenv('MIKROTIK_PROFILE'));

    $port = filter_var($portValue, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 65535],
    ]);

    if (
        !isPrivateRouterIp($host)
        || $port === false
        || $username === ''
        || $password === ''
        || $profile === ''
    ) {
        $message = "<div class='alert error'>MikroTik configuration is missing or invalid. Set all five environment variables. MIKROTIK_HOST must be the router's private WireGuard IP.</div>";
    } else {
        $api = new RouterosAPI();
        $api->port = $port;
        $api->timeout = 5;
        $api->attempts = 1;
        $api->delay = 0;

        try {
            $socketHost = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                ? '[' . $host . ']'
                : $host;

            if (!$api->connect($socketHost, $username, $password)) {
                $detail = $api->error_str ? ' ' . h($api->error_str) : '';
                throw new RuntimeException(
                    'Unable to connect or authenticate with MikroTik. Check the WireGuard route, API service, port, and deployment environment variables.'
                    . $detail
                );
            }

            $code = 'MEO-' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);

            $created = $api->comm('/ip/hotspot/user/add', [
                'name' => $code,
                'password' => $code,
                'profile' => $profile,
                'limit-uptime' => '60m',
                'comment' => 'v1',
            ]);
            throwOnRouterTrap($created);

            // Verify the exact user exists before displaying success.
            $users = $api->comm('/ip/hotspot/user/print', [
                '.proplist' => 'name',
                '?name' => $code,
            ]);
            throwOnRouterTrap($users);

            $voucherExists = false;
            foreach ($users as $user) {
                if (($user['name'] ?? null) === $code) {
                    $voucherExists = true;
                    break;
                }
            }

            if (!$voucherExists) {
                throw new RuntimeException(
                    'MikroTik did not confirm the voucher after creation. Check the router before trying again.'
                );
            }

            $message = "<div class='alert success'>"
                . '<h3>⚡ VOUCHER SUCCESSFULLY GENERATED</h3>'
                . "<div class='voucher-box'>" . h($code) . '</div>'
                . '</div>';
        } catch (Throwable $error) {
            // Never include credentials or exception traces in the response.
            $message = "<div class='alert error'>" . h($error->getMessage()) . '</div>';
        } finally {
            if ($api->connected) {
                $api->disconnect();
            }
        }
    }
}

?>
