<?php

namespace werk365\jwtfusionauth;

use App\jwk;
use App\User;
use Firebase\JWT\JWT;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;
use Spatie\Permission\Models\Role;

class jwtfusionauth
{
    private static function getKid(string $jwt) {
        $tks = explode('.', $jwt);
        if (count($tks) === 3) {
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode($tks[0]));
            if (isset($header->kid)) {
                return $header->kid;
            }
        }
        return null;
    }

    private static function jwkToPem(object $jwk)
    {
        if (isset($jwk->e) && isset($jwk->n)) {
            $rsa = new RSA();
            $rsa->loadKey([
                'e' => new BigInteger(JWT::urlsafeB64Decode($jwk->e), 256),
                'n' => new BigInteger(JWT::urlsafeB64Decode($jwk->n),  256)
            ]);
            return $rsa->getPublicKey();
        }
        return null;
    }

    private static function getPublicKey(string $kid, string $uri) {
        $jwksUrl = sprintf($uri);
        $ch = curl_init($jwksUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
        ]);
        $json = curl_exec($ch);
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
        return null;
    }

    private static function getPem(string $kid, string $uri) {
        $pemUrl = sprintf($uri);
        $ch = curl_init($pemUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
        ]);
        $json = curl_exec($ch);
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
        return null;
    }

    private static function verifyToken(string $jwt, string $uri, bool $jwk = false)
    {
        $publicKey = null;
        $kid = self::getKid($jwt);
        if ($kid) {
            $row = jwk::where('kid', $kid)->orderBy('created_at', 'desc')->first();
            if (false) {
                $publicKey = $row->key;
            }
            else {
                if($jwk){
                    $publicKey = self::getPublicKey($kid, $uri);
                } else {
                    $publicKey = self::getPem($kid, $uri);
                }
                $row = jwk::create(['kid' => $kid, 'key' => $publicKey]);
            }
        }

        if ($publicKey) {
            return JWT::decode($jwt, $publicKey, array('RS256'));
        }
        return null;
    }

    public static function authUser(object $request)
    {
        $jwt = $request->bearerToken();
        $uri = config('jwtfusionauth.useJwk')?config('jwtfusionauth.jwkUri'):config('jwtfusionauth.pemUri');
        $claims = self::verifyToken($jwt, $uri, config('jwtfusionauth.useJwk'));
        if(config('jwtfusionauth.autoCreateUser')){
            $user = User::firstOrNew([config('jwtfusionauth.userId') =>  $claims->sub]);
            $user[config('jwtfusionauth.userId')] = $claims->sub;
            $user->save();
        } else {
            $user = User::where(config('jwtfusionauth.userId'), '=' , $claims->sub)->firstOrFail();
        }
        if(config('jwtfusionauth.usePermissions')){
            if(config('jwtfusionauth.autoCreateRoles')){
                foreach($claims->roles as $role){
                    $db_role = Role::where('name', $role)->first();
                    if(!$db_role){
                        Role::create(['name' => $role]);
                    }
                }
            }
            $user->assignRole($claims->roles);
        }
        return $user;
    }
}
