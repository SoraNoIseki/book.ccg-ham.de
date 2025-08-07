<?php

namespace Soranoiseki\BookGroup\Services;

use Exception;
use GuzzleHttp\Client as HttpClient;
use Spatie\Dropbox\TokenProvider;
use Illuminate\Support\Facades\Cache;

/**
 * https://github.com/spatie/dropbox-api/issues/94#issuecomment-1222553430
 */
class AutoRefreshingDropBoxTokenService implements TokenProvider
{
   private string $key;

   private string $secret;

   private string $refreshToken;

   public function __construct()
   {
       $this->key = env('DROPBOX_APP_KEY');
       $this->secret = env('DROPBOX_APP_SECRET');
       $this->refreshToken = env('DROPBOX_REFRESH_TOKEN');
   }

   public function getToken(): string
   {
    //    return $this->refreshToken();
       $token = Cache::remember('access_token', 14000, function () {
           return $this->refreshToken();
       });
       return $token;
   }

   public function refreshToken(): string|bool
   {
       try {
           $client = new HttpClient();
           $res = $client->request(
               'POST',
               "https://{$this->key}:{$this->secret}@api.dropbox.com/oauth2/token",
               [
                   'form_params' => [
                       'grant_type' => 'refresh_token',
                       'refresh_token' => $this->refreshToken,
                   ],
               ]
           );

           if ($res->getStatusCode() == 200) {
               $response = json_decode($res->getBody(), true);

               return trim(json_encode($response['access_token']), '"');
           } else {
               return false;
           }
       } catch (Exception $e) {
//            ray("[{$e->getCode()}] {$e->getMessage()}");

           return false;
       }
   }
}