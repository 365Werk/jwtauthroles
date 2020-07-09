<?php

namespace werk365\jwtauthroles;

use werk365\jwtauthroles\Models\jwk;
use App\User;
use Firebase\JWT\JWT;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;
use Spatie\Permission\Models\Role;

class jwtauthroles
{

    /**
     * @param string $jwt
     * @return string|null
     */
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
                'n' => new BigInteger(JWT::urlsafeB64Decode($jwk->n),  256)
            ]);
            return $rsa->getPublicKey();
        }
        return null;
    }

    /**
     * @param string $kid
     * @param string $uri
     * @return bool|string|null
     */
    private static function getPublicKey(string $kid, string $uri) {
        $jwksUri = sprintf($uri);
        $ch = curl_init($jwksUri);
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

    /**
     * @param string $kid
     * @param string $uri
     * @return string|null
     */
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

    /**
     * @param string $jwt
     * @param string $uri
     * @param bool $jwk
     * @return object|null
     */
    private static function verifyToken(string $jwt, string $uri, bool $jwk = false)
    {
        $publicKey = null;
        $kid = self::getKid($jwt);
        if ($kid) {
            $row = jwk::where('kid', $kid)->orderBy('created_at', 'desc')->first();
            if ($row) {
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

    /**
     * @param object $request
     * @return mixed
     */
    public static function authUser(object $request)
    {
        $jwt = $request->bearerToken();
        $uri = config('jwtauthroles.useJwk')?config('jwtauthroles.jwkUri'):config('jwtauthroles.pemUri');
        $claims = self::verifyToken($jwt, $uri, config('jwtauthroles.useJwk'));
        if(config('jwtauthroles.autoCreateUser')){
            $user = User::firstOrNew([config('jwtauthroles.userId') =>  $claims->sub]);
            $user[config('jwtauthroles.userId')] = $claims->sub;
            $user->save();
        } else {
            $user = User::where(config('jwtauthroles.userId'), '=' , $claims->sub)->firstOrFail();
        }
        if(config('jwtauthroles.usePermissions')){
            if(config('jwtauthroles.autoCreateRoles')){
                foreach($claims->roles as $role){
                    $db_role = Role::where('name', $role)->first();
                    if(!$db_role){
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
