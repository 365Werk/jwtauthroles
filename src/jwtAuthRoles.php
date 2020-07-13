<?php

namespace werk365\jwtauthroles;

use App\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;
use Spatie\Permission\Models\Role;
use werk365\jwtauthroles\Exceptions\authException;
use werk365\jwtauthroles\Models\JwtKey;

class jwtAuthRoles
{
    /**
     * @param string $jwt
     * @return string|null
     */
    private static function getKid(string $jwt)
    {
        if (Str::is('*.*.*', $jwt)) {
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode(Str::before($jwt, '.')));
            if (isset($header->alg) && $header->alg !== config('jwtAuthRoles.alg')) {
                throw authException::auth(422, 'Invalid algorithm');
            }

            return $header->kid ?? null;
        } else {
            throw authException::auth(422, 'Malformed JWT');
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
        throw authException::auth(500, 'Malformed jwk');
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
        throw authException::auth(404, 'jwks endpoint not found');
    }

    /**
     * @param string $kid
     * @param string $uri
     * @return string|null
     */
    private static function getPem(string $kid, string $uri)
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
        throw authException::auth(404, 'pem endpoint not found');
    }

    /**
     * @param string $jwt
     * @param string $uri
     * @param bool $jwk
     * @return object
     */
    private static function verifyToken(string $jwt, string $uri, bool $jwk = false)
    {
        $kid = self::getKid($jwt);
        if (! $kid) {
            throw authException::auth(422, 'Malformed JWT');
        }
        if (config('jwtAuthRoles.cache.enabled')) {
            if (config('jwtAuthRoles.cache.type') === 'database') {
                $row = JwtKey::where('kid', $kid)->orderBy('created_at', 'desc')->first('key');
            }
        }

        $publicKey = $row->key ?? $jwk ? self::getJwk($kid, $uri) : self::getPem($kid, $uri);
        if (! isset($publicKey) || ! $publicKey) {
            throw authException::auth(500, 'Unable to validate JWT');
        }

        if (config('jwtAuthRoles.cache.enabled')) {
            if (config('jwtAuthRoles.cache.type') === 'database') {
                $row = $row ?? JwtKey::create(['kid' => $kid, 'key' => $publicKey]);
            }
        }

        return JWT::decode($jwt, $publicKey, [config('jwtAuthRoles.alg')]);
    }

    /**
     * @param object $request
     * @return mixed
     */
    public static function authUser(object $request)
    {
        $jwt = $request->bearerToken();
        $uri = config('jwtAuthRoles.useJwk') ? config('jwtAuthRoles.jwkUri') : config('jwtAuthRoles.pemUri');
        $claims = self::verifyToken($jwt, $uri, config('jwtAuthRoles.useJwk'));
        if (config('jwtAuthRoles.autoCreateUser')) {
            $user = User::firstOrNew([config('jwtAuthRoles.userId') =>  $claims->sub]);
            $user[config('jwtAuthRoles.userId')] = $claims->sub;
            $user->save();
        } else {
            $user = User::where(config('jwtAuthRoles.userId'), '=', $claims->sub)->firstOrFail();
        }
        if (config('jwtAuthRoles.usePermissions')) {
            if (config('jwtAuthRoles.autoCreateRoles')) {
                foreach ($claims->roles as $role) {
                    $db_role = Role::where('name', $role)->first();
                    if (! $db_role) {
                        Role::create(['name' => $role]);
                    }
                }
            }
            // Remove previously assigned roles and update from JWT
            $user->syncRoles($claims->roles);
        }

        return $user;
    }
}
