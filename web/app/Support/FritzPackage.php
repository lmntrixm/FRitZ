<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/**
 * .fritz package: an .xlsx product sheet encrypted with AES-256-GCM.
 *
 * Layout (binary): "FRITZ1" | salt 16 | iv 12 | key-check 8 | ciphertext | tag 16
 * - key = PBKDF2-SHA256(password, salt, 200 000 rounds, 32 bytes)
 * - key-check = first 8 bytes of SHA-256(key . "fritz-check"): tells «wrong password» from «tampered file»
 * - the 42-byte header is the GCM additional data, so it cannot be altered either
 * The browser tool (resources/views/admin/update/packer.blade.php) writes the same layout with WebCrypto.
 */
class FritzPackage
{
    public const MAGIC = 'FRITZ1';

    public const ROUNDS = 200000;

    public const WRONG_KEY = 'wrong-key';

    public const TAMPERED = 'tampered';

    public static function encrypt(string $plain, string $password): string
    {
        $salt = random_bytes(16);
        $iv = random_bytes(12);
        $key = self::key($password, $salt);
        $header = self::MAGIC.$salt.$iv.self::check($key);
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $header, 16);

        return $header.$cipher.$tag;
    }

    /** @throws RuntimeException with code-like message WRONG_KEY / TAMPERED / not-a-package */
    public static function decrypt(string $blob, string $password): string
    {
        if (strlen($blob) < 6 + 16 + 12 + 8 + 16 || ! str_starts_with($blob, self::MAGIC)) {
            throw new RuntimeException('not-a-package');
        }
        $salt = substr($blob, 6, 16);
        $iv = substr($blob, 22, 12);
        $check = substr($blob, 34, 8);
        $key = self::key($password, $salt);
        if (! hash_equals(self::check($key), $check)) {
            throw new RuntimeException(self::WRONG_KEY);
        }
        $header = substr($blob, 0, 42);
        $plain = openssl_decrypt(substr($blob, 42, -16), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, substr($blob, -16), $header);
        if ($plain === false) {
            throw new RuntimeException(self::TAMPERED);
        }

        return $plain;
    }

    private static function key(string $password, string $salt): string
    {
        return hash_pbkdf2('sha256', $password, $salt, self::ROUNDS, 32, true);
    }

    private static function check(string $key): string
    {
        return substr(hash('sha256', $key.'fritz-check', true), 0, 8);
    }

    /* ---- the package password, kept encrypted with APP_KEY in settings ---- */

    public static function password(): ?string
    {
        $enc = Setting::get('fritz_key');

        return $enc ? Crypt::decryptString($enc) : null;
    }

    public static function setPassword(string $password): void
    {
        Setting::put('fritz_key', Crypt::encryptString($password));
        Setting::put('fritz_key_meta', ['fp' => self::fingerprint($password), 'at' => now()->toIso8601String(), 'by' => auth()->user()?->name]);
    }

    /** «9F:2C:…:A1:4E»: safe to show, enough to compare with the file maker. */
    public static function fingerprint(string $password): string
    {
        $h = strtoupper(hash('sha256', 'fritz-fp:'.$password));

        return implode(':', str_split(substr($h, 0, 4), 2)).':…:'.implode(':', str_split(substr($h, -4), 2));
    }
}
