<?php

namespace werk365\JwtAuthRoles;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;
use werk365\jwtauthroles\Exceptions\AuthException;
use werk365\jwtauthroles\Models\JwtKey;
use werk365\jwtauthroles\Models\JwtUser;

class JwtAuthRoles
{
    private static function getKid(string $jwt): ?string
    {
        if (Str::is('*.*.*', $jwt)) {
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode(Str::before($jwt, '.')));
            if (isset($header->alg) && $header->alg !== config('jwtauthroles.alg')) {
                throw AuthException::auth(422, 'Invalid algorithm');
            }

            return $header->kid ?? null;
        } else {
            throw AuthException::auth(422, 'Malformed JWT');
        }
    }

    private static function getClaims(string $jwt): ?object
    {
        if (Str::is('*.*.*', $jwt)) {
            $claims = explode('.', $jwt);
            $claims = JWT::jsonDecode(JWT::urlsafeB64Decode($claims[1]));

            return $claims ?? null;
        } else {
            throw AuthException::auth(422, 'Malformed JWT');
        }
    }

    /**
     * @param object $jwk
     * @return bool|string|null
     */
    private static function jwkToPem(object $jwk)
    {
        if (isset($jwk->e) && isset($jwk->n)) {
            $rsa = new RSA();
            $rsa->loadKey([
                'e' => new BigInteger(JWT::urlsafeB64Decode($jwk->e), 256),
                'n' => new BigInteger(JWT::urlsafeB64Decode($jwk->n), 256),
            ]);

            return $rsa->getPublicKey();
        }
        throw AuthException::auth(500, 'Malformed jwk');
    }

    /**
     * @param string $kid
     * @param string $uri
     * @return bool|string|null
     */
    private static function getJwk(string $kid, string $uri)
    {
        $response = Http::get($uri);
        $json = $response->getBody();
        if ($json) {
            $jwks = json_decode($json, false);
            if ($jwks && isset($jwks->keys) && is_array($jwks->keys)) {
                foreach ($jwks->keys as $jwk) {
                    if ($jwk->kid === $kid) {
                        return self::jwkToPem($jwk);
                    }
                }
            }
        }
        throw AuthException::auth(404, 'jwks endpoint not found');
    }

    private static function getPem(string $kid, string $uri): ?string
    {
        $response = Http::get($uri);
        $json = $response->getBody();
        if ($json) {
            $pems = json_decode($json, false);
            if ($pems && isset($pems->publicKeys) && is_object($pems->publicKeys)) {
                foreach ($pems->publicKeys as $key=>$pem) {
                    if ($key === $kid) {
                        return $pem;
                    }
                }
            }
        }
        throw AuthException::auth(404, 'pem endpoint not found');
    }

    private static function verifyToken(string $jwt, string $uri, bool $jwk = false): object
    {
        $kid = self::getKid($jwt);
        if (! $kid) {
            throw AuthException::auth(422, 'Malformed JWT');
        }
        if (config('jwtauthroles.cache.enabled')) {
            if (config('jwtauthroles.cache.type') === 'database') {
                $row = JwtKey::where('kid', $kid)
                    ->orderBy('created_at', 'desc')
                    ->first('key');
            }
        }

        $publicKey = $row->key
            ?? $jwk
                ? self::getJwk($kid, $uri)
                : self::getPem($kid, $uri);

        if (! isset($publicKey) || ! $publicKey) {
            throw AuthException::auth(500, 'Unable to validate JWT');
        }

        if (config('jwtauthroles.cache.enabled')) {
            if (config('jwtauthroles.cache.type') === 'database') {
                $row = $row ?? JwtKey::create(['kid' => $kid, 'key' => $publicKey]);
            }
        }

        return JWT::decode($jwt, $publicKey, [config('jwtauthroles.alg')]);
    }

    /** @return mixed */
    public static function authUser(object $request)
    {
        $jwt = $request->bearerToken();

        $uri = config('jwtauthroles.useJwk')
            ? config('jwtauthroles.jwkUri')
            : config('jwtauthroles.pemUri');

        if (! config('jwtauthroles.validateJwt')) {
            $claims = self::getClaims($jwt);
        } else {
            $claims = self::verifyToken($jwt, $uri, config('jwtauthroles.useJwk'));
        }

        if (config('jwtauthroles.useDB')) {
            if (config('jwtauthroles.autoCreateUser')) {
                $user = JwtUser::firstOrNew([config('jwtauthroles.userId') => $claims->sub]);
                $user[config('jwtauthroles.userId')] = $claims->sub;
                $user->roles = json_encode($claims->roles);
                $user->claims = json_encode($claims);
                $user->save();
            } else {
                $user = JwtUser::where(config('jwtauthroles.userId'), '=', $claims->sub)->firstOrFail();
                $user->roles = json_encode($claims->roles);
                $user->claims = json_encode($claims);
                $user->save();
            }
        } else {
            $user = new JwtUser();
            $user->uuid = $claims->sub;
            $user->roles = $claims->roles;
            $user->claims = $claims;
        }

        return $user;
    }
}
