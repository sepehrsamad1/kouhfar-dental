<?php

declare(strict_types=1);

namespace App\Services;

final class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD_SECONDS = 30;

    private const DIGITS = 6;

    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function verify(string $secret, string $code, ?int $timestamp = null, int $window = 1): bool
    {
        return $this->matchingCounter($secret, $code, $timestamp, $window) !== null;
    }

    public function matchingCounter(string $secret, string $code, ?int $timestamp = null, int $window = 1): ?int
    {
        if (preg_match('/^[0-9]{6}$/D', $code) !== 1 || $window < 0 || $window > 2) {
            return null;
        }

        $key = $this->base32Decode($secret);

        if ($key === null || $key === '') {
            return null;
        }

        $counter = intdiv($timestamp ?? time(), self::PERIOD_SECONDS);

        for ($offset = -$window; $offset <= $window; $offset++) {
            $candidate = $counter + $offset;

            if ($candidate < 0) {
                continue;
            }

            if (hash_equals($this->codeForCounter($key, $candidate), $code)) {
                return $candidate;
            }
        }

        return null;
    }

    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer.':'.$account);

        return 'otpauth://totp/'.$label.'?secret='.rawurlencode($secret)
            .'&issuer='.rawurlencode($issuer)
            .'&algorithm=SHA1&digits='.self::DIGITS.'&period='.self::PERIOD_SECONDS;
    }

    private function codeForCounter(string $key, int $counter): string
    {
        $message = pack('N2', ($counter >> 32) & 0xFFFFFFFF, $counter & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $message, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $input): string
    {
        $bits = '';

        foreach (str_split($input) as $character) {
            $bits .= str_pad(decbin(ord($character)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }

            $encoded .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    private function base32Decode(string $input): ?string
    {
        $input = strtoupper(rtrim($input, '='));

        if ($input === '' || preg_match('/^[A-Z2-7]+$/D', $input) !== 1) {
            return null;
        }

        $bits = '';

        foreach (str_split($input) as $character) {
            $position = strpos(self::BASE32_ALPHABET, $character);

            if ($position === false) {
                return null;
            }

            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $decoded .= chr(bindec($chunk));
            }
        }

        return $decoded;
    }
}
