<?php

namespace App\Service;

use App\Entity\PasskeyCredential;
use App\Entity\User;
use App\Repository\PasskeyCredentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PasskeyService
{
    private const CHALLENGE_SESSION_KEY = '_passkey_challenge';
    private const REG_CHALLENGE_SESSION_KEY = '_passkey_reg_challenge';

    public function __construct(
        private EntityManagerInterface $em,
        private PasskeyCredentialRepository $repo,
        private RequestStack $requestStack,
        private string $rpId,
        private string $rpName,
    ) {
    }

    /**
     * Get effective RP ID from current request host.
     * WebAuthn requires RP ID to match the origin's effective domain.
     */
    private function getEffectiveRpId(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            return $request->getHost(); // e.g. "localhost" or "127.0.0.1"
        }
        return $this->rpId;
    }

    /**
     * Get the current origin (scheme://host[:port]) for origin validation.
     */
    private function getCurrentOrigin(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $scheme = $request->getScheme();
            $host = $request->getHost();
            $port = $request->getPort();

            $origin = $scheme . '://' . $host;
            // Only append port if non-standard
            if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
                $origin .= ':' . $port;
            }
            return $origin;
        }
        return 'http://' . $this->rpId;
    }

    // ── Registration flow ──────────────────────────────────────────

    public function generateRegistrationOptions(User $user): array
    {
        $challenge = random_bytes(32);
        $this->requestStack->getSession()->set(self::REG_CHALLENGE_SESSION_KEY, base64_encode($challenge));

        $existingKeys = $this->repo->findByUser($user);
        $excludeCredentials = array_map(fn(PasskeyCredential $c) => [
            'id' => $c->getCredentialId(),
            'type' => 'public-key',
            'transports' => $c->getTransports() ?? [],
        ], $existingKeys);

        return [
            'challenge' => $this->base64urlEncode($challenge),
            'rp' => [
                'name' => $this->rpName,
                'id' => $this->getEffectiveRpId(),
            ],
            'user' => [
                'id' => $this->base64urlEncode((string) $user->getId()),
                'name' => $user->getUserIdentifier(),
                'displayName' => $user->getDisplayName(),
            ],
            'pubKeyCredParams' => [
                ['alg' => -7, 'type' => 'public-key'],   // ES256
                ['alg' => -257, 'type' => 'public-key'],  // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'residentKey' => 'preferred',
                'userVerification' => 'preferred',
            ],
            'excludeCredentials' => $excludeCredentials,
        ];
    }

    public function verifyRegistration(User $user, array $credential, string $deviceName): PasskeyCredential
    {
        $session = $this->requestStack->getSession();
        $expectedChallenge = $session->get(self::REG_CHALLENGE_SESSION_KEY);
        $session->remove(self::REG_CHALLENGE_SESSION_KEY);

        if (!$expectedChallenge) {
            throw new \RuntimeException('No registration challenge in session.');
        }

        $clientDataJSON = $this->base64urlDecode($credential['response']['clientDataJSON']);
        $clientData = json_decode($clientDataJSON, true);

        // Verify type
        if (($clientData['type'] ?? '') !== 'webauthn.create') {
            throw new \RuntimeException('Invalid clientData type.');
        }

        // Verify challenge
        if (($clientData['challenge'] ?? '') !== $this->base64urlEncode(base64_decode($expectedChallenge))) {
            throw new \RuntimeException('Challenge mismatch.');
        }

        // Verify origin — must match current request origin
        $origin = $clientData['origin'] ?? '';
        $expectedOrigin = $this->getCurrentOrigin();
        if ($origin !== $expectedOrigin) {
            throw new \RuntimeException('Origin mismatch: ' . $origin);
        }

        // Parse attestationObject to extract public key
        $attestationObject = $this->base64urlDecode($credential['response']['attestationObject']);
        $decoded = $this->decodeCborAttestation($attestationObject);

        $authData = $decoded['authData'];
        $credentialData = $this->parseAuthData($authData);

        $passkeyCredential = new PasskeyCredential();
        $passkeyCredential->setUser($user);
        $passkeyCredential->setCredentialId($credential['id']);
        $passkeyCredential->setPublicKey(base64_encode($credentialData['publicKey']));
        $passkeyCredential->setSignCount($credentialData['signCount']);
        $passkeyCredential->setName($deviceName);
        $passkeyCredential->setTransports($credential['response']['transports'] ?? []);

        $this->em->persist($passkeyCredential);
        $this->em->flush();

        return $passkeyCredential;
    }

    // ── Authentication flow ────────────────────────────────────────

    public function generateAuthenticationOptions(): array
    {
        $challenge = random_bytes(32);
        $this->requestStack->getSession()->set(self::CHALLENGE_SESSION_KEY, base64_encode($challenge));

        return [
            'challenge' => $this->base64urlEncode($challenge),
            'timeout' => 60000,
            'rpId' => $this->getEffectiveRpId(),
            'userVerification' => 'preferred',
            'allowCredentials' => [], // Empty = discoverable credential (passkey)
        ];
    }

    public function verifyAuthentication(array $credential): ?User
    {
        $session = $this->requestStack->getSession();
        $expectedChallenge = $session->get(self::CHALLENGE_SESSION_KEY);
        $session->remove(self::CHALLENGE_SESSION_KEY);

        if (!$expectedChallenge) {
            throw new \RuntimeException('No authentication challenge in session.');
        }

        // Find the credential
        $passkey = $this->repo->findByCredentialId($credential['id']);
        if (!$passkey) {
            return null;
        }

        $clientDataJSON = $this->base64urlDecode($credential['response']['clientDataJSON']);
        $clientData = json_decode($clientDataJSON, true);

        // Verify type
        if (($clientData['type'] ?? '') !== 'webauthn.get') {
            throw new \RuntimeException('Invalid clientData type.');
        }

        // Verify challenge
        if (($clientData['challenge'] ?? '') !== $this->base64urlEncode(base64_decode($expectedChallenge))) {
            throw new \RuntimeException('Challenge mismatch.');
        }

        // Verify origin — must match current request origin
        $origin = $clientData['origin'] ?? '';
        $expectedOrigin = $this->getCurrentOrigin();
        if ($origin !== $expectedOrigin) {
            throw new \RuntimeException('Origin mismatch: ' . $origin);
        }

        // Parse authenticator data
        $authData = $this->base64urlDecode($credential['response']['authenticatorData']);
        $signCount = unpack('N', substr($authData, 33, 4))[1] ?? 0;

        // Verify signature
        $signature = $this->base64urlDecode($credential['response']['signature']);
        $publicKeyPem = $this->coseToPem(base64_decode($passkey->getPublicKey()));

        // Data to verify: authData || sha256(clientDataJSON)
        $dataToVerify = $authData . hash('sha256', $clientDataJSON, true);

        $valid = openssl_verify($dataToVerify, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256);
        if ($valid !== 1) {
            throw new \RuntimeException('Signature verification failed.');
        }

        // Update sign count (replay protection)
        if ($signCount > 0 && $signCount <= $passkey->getSignCount()) {
            throw new \RuntimeException('Possible credential cloning detected.');
        }

        $passkey->setSignCount($signCount);
        $passkey->setLastUsedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $passkey->getUser();
    }

    /** @return PasskeyCredential[] */
    public function getUserPasskeys(User $user): array
    {
        return $this->repo->findByUser($user);
    }

    public function deletePasskey(int $id, User $user): bool
    {
        $passkey = $this->repo->find($id);
        if (!$passkey || $passkey->getUser()->getId() !== $user->getId()) {
            return false;
        }

        $this->em->remove($passkey);
        $this->em->flush();
        return true;
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    private function decodeCborAttestation(string $data): array
    {
        // Minimal CBOR map decoder for attestation object
        // Expects: {fmt: "none", attStmt: {}, authData: <bytes>}
        $offset = 0;
        $byte = ord($data[$offset]);
        $mapLen = $byte & 0x1f;
        if ($mapLen > 5) { $mapLen = ord($data[++$offset]); }
        $offset++;

        $result = [];
        for ($i = 0; $i < $mapLen; $i++) {
            $keyByte = ord($data[$offset]);
            $keyLen = $keyByte & 0x1f;
            if ($keyLen >= 24) { $keyLen = ord($data[++$offset]); }
            $offset++;
            $key = substr($data, $offset, $keyLen);
            $offset += $keyLen;

            $valByte = ord($data[$offset]);
            $majorType = ($valByte >> 5) & 0x07;

            if ($majorType === 3) { // text string
                $valLen = $valByte & 0x1f;
                if ($valLen >= 24) { $valLen = ord($data[++$offset]); }
                $offset++;
                $result[$key] = substr($data, $offset, $valLen);
                $offset += $valLen;
            } elseif ($majorType === 2) { // byte string
                $valLen = $valByte & 0x1f;
                $offset++;
                if ($valLen === 24) { $valLen = ord($data[$offset]); $offset++; }
                elseif ($valLen === 25) { $valLen = unpack('n', substr($data, $offset, 2))[1]; $offset += 2; }
                elseif ($valLen === 26) { $valLen = unpack('N', substr($data, $offset, 4))[1]; $offset += 4; }
                $result[$key] = substr($data, $offset, $valLen);
                $offset += $valLen;
            } elseif ($majorType === 5) { // map (skip empty attStmt)
                $subMapLen = $valByte & 0x1f;
                $offset++;
                // Skip contents (empty map for attestation=none)
                $result[$key] = [];
            } else {
                $offset++;
            }
        }

        return $result;
    }

    private function parseAuthData(string $authData): array
    {
        // rpIdHash (32) + flags (1) + signCount (4) = 37 bytes header
        $flags = ord($authData[32]);
        $signCount = unpack('N', substr($authData, 33, 4))[1];

        // Attested credential data starts at byte 37
        // aaguid (16) + credIdLen (2) + credId + publicKey
        $credIdLen = unpack('n', substr($authData, 53, 2))[1];
        $publicKey = substr($authData, 55 + $credIdLen);

        return [
            'signCount' => $signCount,
            'publicKey' => $publicKey,
        ];
    }

    private function coseToPem(string $coseKey): string
    {
        // Parse COSE key (ES256 — most common for platform authenticators)
        $offset = 0;
        $byte = ord($coseKey[$offset]);
        $mapLen = $byte & 0x1f;
        if ($mapLen >= 24) { $mapLen = ord($coseKey[++$offset]); }
        $offset++;

        $kty = null;
        $x = null;
        $y = null;

        for ($i = 0; $i < $mapLen; $i++) {
            $keyByte = ord($coseKey[$offset]);
            $isNeg = (($keyByte >> 5) & 0x07) === 1;
            $keyVal = $keyByte & 0x1f;
            if ($isNeg) { $keyVal = -1 - $keyVal; }
            $offset++;

            $valByte = ord($coseKey[$offset]);
            $valMajor = ($valByte >> 5) & 0x07;
            $valLen = $valByte & 0x1f;

            if ($valMajor === 0) { // unsigned int
                if ($valLen < 24) {
                    $val = $valLen;
                } else {
                    $val = ord($coseKey[++$offset]);
                }
                $offset++;
                if ($keyVal === 1) { $kty = $val; }
            } elseif ($valMajor === 1) { // negative int
                $val = -1 - $valLen;
                $offset++;
                // alg = -7 for ES256
            } elseif ($valMajor === 2) { // byte string
                if ($valLen >= 24) { $valLen = ord($coseKey[++$offset]); }
                $offset++;
                $bytes = substr($coseKey, $offset, $valLen);
                $offset += $valLen;
                if ($keyVal === -2) { $x = $bytes; }
                elseif ($keyVal === -3) { $y = $bytes; }
            } else {
                $offset++;
            }
        }

        if ($x && $y) {
            // EC P-256 uncompressed point: 0x04 || x || y
            $point = "\x04" . $x . $y;

            // DER encode as SubjectPublicKeyInfo for EC P-256
            $ecOid = hex2bin('06082a8648ce3d030107'); // OID 1.2.840.10045.3.1.7 (P-256)
            $ecPubOid = hex2bin('06072a8648ce3d0201');  // OID 1.2.840.10045.2.1 (EC)

            $algId = "\x30" . chr(strlen($ecPubOid . $ecOid)) . $ecPubOid . $ecOid;
            $bitString = "\x03" . chr(strlen($point) + 1) . "\x00" . $point;
            $spki = "\x30" . chr(strlen($algId . $bitString)) . $algId . $bitString;

            $pem = "-----BEGIN PUBLIC KEY-----\n"
                . chunk_split(base64_encode($spki), 64, "\n")
                . "-----END PUBLIC KEY-----";

            return $pem;
        }

        throw new \RuntimeException('Unsupported COSE key type. Only ES256 (P-256) is supported.');
    }
}
