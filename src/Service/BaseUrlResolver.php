<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

final class BaseUrlResolver
{
    private const DEFAULT_PORT = 8000;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{baseUrl: string, ip: string, port: int, interface: string, source: string}
     */
    public function resolveLanBaseUrl(): array
    {
        $configured = trim((string) ($_ENV['APP_URL'] ?? ''));
        if ('' !== $configured && $this->isHostReachable($configured)) {
            $host = (string) parse_url($configured, PHP_URL_HOST);
            $port = (int) (parse_url($configured, PHP_URL_PORT) ?: $this->resolvePort());

            return [
                'baseUrl' => rtrim($configured, '/'),
                'ip' => $host,
                'port' => $port,
                'interface' => 'configured',
                'source' => 'APP_URL',
            ];
        }

        [$ip, $interface, $source] = $this->detectLanIpWithSource();
        $port = $this->resolvePort();

        return [
            'baseUrl' => sprintf('http://%s:%d', $ip, $port),
            'ip' => $ip,
            'port' => $port,
            'interface' => $interface,
            'source' => $source,
        ];
    }

    private function resolvePort(): int
    {
        $configured = trim((string) ($_ENV['APP_URL'] ?? ''));
        if ('' !== $configured) {
            $parsedPort = parse_url($configured, PHP_URL_PORT);
            if (\is_int($parsedPort) && $parsedPort > 0) {
                return $parsedPort;
            }
        }

        $envPort = (int) ($_ENV['APP_PORT'] ?? self::DEFAULT_PORT);

        return $envPort > 0 ? $envPort : self::DEFAULT_PORT;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function detectLanIpWithSource(): array
    {
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? null;
        if (\is_string($serverAddr) && $this->isLanIp($serverAddr)) {
            return [$serverAddr, 'server_addr', 'server'];
        }

        $hostnameIp = gethostbyname(gethostname());
        if ($this->isLanIp($hostnameIp)) {
            return [$hostnameIp, 'hostname', 'dns'];
        }

        if (\DIRECTORY_SEPARATOR === '\\') {
            $windows = $this->detectFromWindowsIpConfig();
            if (null !== $windows) {
                return $windows;
            }
        }

        if (\extension_loaded('sockets')) {
            $socketIp = $this->detectFromUdpSocket();
            if (null !== $socketIp) {
                return [$socketIp, 'udp_socket', 'socket'];
            }
        }

        $hostnameI = $this->detectFromHostnameCommand();
        if (null !== $hostnameI) {
            return [$hostnameI, 'hostname -I', 'shell'];
        }

        $this->logger->error('Unable to detect active LAN IP for QR generation.', [
            'appUrl' => $_ENV['APP_URL'] ?? null,
            'appPort' => $_ENV['APP_PORT'] ?? null,
        ]);

        throw new \RuntimeException('Unable to detect active LAN IPv4. Set APP_URL to your active LAN host.');
    }

    /**
     * @return array{0: string, 1: string, 2: string}|null
     */
    private function detectFromWindowsIpConfig(): ?array
    {
        $raw = @shell_exec('ipconfig');
        if (!\is_string($raw) || '' === trim($raw)) {
            return null;
        }

        $blocks = preg_split('/\R{2,}/', $raw) ?: [];
        $candidates = [];
        foreach ($blocks as $block) {
            $interface = $this->extractWindowsInterfaceName($block);
            if (null === $interface || $this->isVirtualInterface($interface)) {
                continue;
            }

            if (!preg_match_all('/(?:IPv4|Adresse IPv4)[^:]*:\s*([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/iu', $block, $matches)) {
                continue;
            }

            foreach (($matches[1] ?? []) as $ip) {
                if ($this->isLanIp($ip)) {
                    $score = $this->interfaceScore($interface);
                    $candidates[] = ['ip' => $ip, 'interface' => $interface, 'score' => $score];
                }
            }
        }

        if ([] === $candidates) {
            return null;
        }

        usort($candidates, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        $selected = $candidates[0];

        return [$selected['ip'], $selected['interface'], 'ipconfig'];
    }

    private function extractWindowsInterfaceName(string $block): ?string
    {
        $firstLine = trim((string) (preg_split('/\R/', $block)[0] ?? ''));
        if ('' === $firstLine) {
            return null;
        }

        $name = preg_replace('/\s*:\s*$/', '', $firstLine);
        $name = preg_replace('/^(Wireless LAN adapter|Ethernet adapter|Carte.*|Adaptateur.*)\s*/iu', '', (string) $name);
        $name = trim((string) $name);

        return '' === $name ? null : $name;
    }

    private function detectFromUdpSocket(): ?string
    {
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (false === $socket) {
            return null;
        }

        @socket_connect($socket, '8.8.8.8', 53);
        $name = null;
        $port = null;
        @socket_getsockname($socket, $name, $port);
        @socket_close($socket);

        if (\is_string($name) && $this->isLanIp($name)) {
            return $name;
        }

        return null;
    }

    private function detectFromHostnameCommand(): ?string
    {
        $raw = @shell_exec('hostname -I');
        if (!\is_string($raw) || '' === trim($raw)) {
            return null;
        }

        foreach (preg_split('/\s+/', trim($raw)) ?: [] as $candidate) {
            if ($this->isLanIp($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function interfaceScore(string $interface): int
    {
        $i = strtolower($interface);
        if (str_contains($i, 'wi-fi') || str_contains($i, 'wifi') || str_contains($i, 'wireless') || str_contains($i, 'wlan')) {
            return 100;
        }
        if (str_contains($i, 'ethernet')) {
            return 80;
        }

        return 50;
    }

    private function isVirtualInterface(string $interface): bool
    {
        $i = strtolower($interface);
        foreach (['vmware', 'virtualbox', 'vbox', 'docker', 'hyper-v', 'vethernet', 'loopback', 'bluetooth', 'tunnel', 'hamachi', 'zerotier', 'wsl'] as $blocked) {
            if (str_contains($i, $blocked)) {
                return true;
            }
        }

        return false;
    }

    private function isHostReachable(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!\is_string($host) || '' === $host) {
            return false;
        }

        return true;
    }

    private function isLanIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        return str_starts_with($ip, '10.')
            || str_starts_with($ip, '192.168.')
            || (bool) preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $ip);
    }
}
