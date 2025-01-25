<?php

namespace App\Services;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class JitsiJWTService
{
    protected $appId;
    protected $privateKey;

    public function __construct()
    {
        $this->appId = config('services.jitsi.app_id');
        $this->privateKey =  config('services.jitsi.private_key_path');
    }

    public function generateToken($room, $user, $startTime, $endTime)
    {
        Log::alert('in');
        $now = Carbon::now();
        $exp = strtotime($endTime);
        $nbf = strtotime($startTime);
        Log::alert($startTime);
        Log::alert($nbf);
        $jwk = JWKFactory::createFromKeyFile($this->privateKey);

        $algorithm = new AlgorithmManager([
            new RS256()
        ]);
        $jwsBuilder = new JWSBuilder($algorithm);

        $payload = [
            "aud" => "jitsi",
            "iss" => 'chat',
            "sub" => $this->appId,
            "room" => $room,
            "iat" => $nbf,
            "exp" => $exp,
            "nbf" => $nbf,
            "context" => [
                "user" => [
                    "name" =>  $user->name,
                    "email" => $user->email,
                    "id" => $user->id,
                    "moderator" => true
                ],
                "features" => [
                    "livestreaming" => true,
                    "outbound-call" => false,
                    "sip-outbound-call" => false,
                    "transcription" => false,
                    "recording" => true
                ]
            ]
        ];
        $jws = $jwsBuilder
            ->create()
            ->withPayload(json_encode($payload))
            ->addSignature($jwk, [
                'alg' => 'RS256',
                'kid' => "vpaas-magic-cookie-b11bfb288d96498fa5909dae05198972/7c4797",
                'typ' => 'JWT'
            ])
            ->build();

        $serializer = new CompactSerializer();

        return $serializer->serialize($jws, 0);
    }
}
