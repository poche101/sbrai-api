<?php

namespace App\Helpers;

class AgoraTokenBuilder
{
    const ROLE_PUBLISHER  = 1;
    const ROLE_SUBSCRIBER = 2;

    public static function buildTokenWithUid(
        string $appId,
        string $appCertificate,
        string $channelName,
        int    $uid,
        int    $role,
        int    $tokenExpire,
        int    $privilegeExpire = 0
    ): string {
        return self::buildTokenWithUserAccount(
            $appId, $appCertificate, $channelName,
            (string) $uid, $role, $tokenExpire, $privilegeExpire
        );
    }

    public static function buildTokenWithUserAccount(
        string $appId,
        string $appCertificate,
        string $channelName,
        string $account,
        int    $role,
        int    $tokenExpire,
        int    $privilegeExpire = 0
    ): string {
        $token = new AccessToken2($appId, $appCertificate, $tokenExpire);

        $serviceRtc = new ServiceRtc($channelName, $account);
        $serviceRtc->addPrivilege(ServiceRtc::PRIVILEGE_JOIN_CHANNEL, $privilegeExpire);

        if ($role === self::ROLE_PUBLISHER) {
            $serviceRtc->addPrivilege(ServiceRtc::PRIVILEGE_PUBLISH_AUDIO_STREAM, $privilegeExpire);
            $serviceRtc->addPrivilege(ServiceRtc::PRIVILEGE_PUBLISH_VIDEO_STREAM, $privilegeExpire);
        }

        $token->addService($serviceRtc);
        return $token->build();
    }
}

class ServiceRtc
{
    const SERVICE_TYPE                   = 1;
    const PRIVILEGE_JOIN_CHANNEL         = 1;
    const PRIVILEGE_PUBLISH_AUDIO_STREAM = 2;
    const PRIVILEGE_PUBLISH_VIDEO_STREAM = 3;
    const PRIVILEGE_PUBLISH_DATA_STREAM  = 4;

    private string $channelName;
    private string $uid;
    public  array  $privileges = [];

    public function __construct(string $channelName, string $uid)
    {
        $this->channelName = $channelName;
        $this->uid         = $uid;
    }

    public function addPrivilege(int $privilege, int $expire): void
    {
        $this->privileges[$privilege] = $expire;
    }

    public function pack(): string
    {
        $data  = pack('n', self::SERVICE_TYPE);
        $data .= pack('n', strlen($this->channelName)) . $this->channelName;
        $data .= pack('n', strlen($this->uid))         . $this->uid;
        $data .= pack('n', count($this->privileges));

        foreach ($this->privileges as $privilege => $expire) {
            $data .= pack('n', $privilege);
            $data .= pack('N', $expire);
        }

        return $data;
    }
}

class AccessToken2
{
    const VERSION = '007';

    private string $appId;
    private string $appCertificate;
    private int    $expire;
    private int    $issueTs;
    private int    $salt;
    private array  $services = [];

    public function __construct(string $appId, string $appCertificate, int $expire)
    {
        $this->appId          = $appId;
        $this->appCertificate = $appCertificate;
        $this->expire         = $expire;
        $this->issueTs        = time();
        $this->salt           = rand(1, 99999999);
    }

    public function addService(ServiceRtc $service): void
    {
        $this->services[] = $service;
    }

    public function build(): string
    {
        $msg  = pack('N', $this->issueTs);
        $msg .= pack('N', $this->expire);
        $msg .= pack('N', $this->salt);
        $msg .= pack('n', count($this->services));

        foreach ($this->services as $service) {
            $msg .= $service->pack();
        }

        // ✅ CORRECT signing order per Agora AccessToken2 spec:
        // 1. signing = HMAC-SHA256(appCertificate, appId + issueTs + salt + msg)
        $signing  = hash_hmac('sha256', $this->appId,                    $this->appCertificate, true);
        $signing  = hash_hmac('sha256', pack('N', $this->issueTs),       $signing,              true);
        $signing  = hash_hmac('sha256', pack('N', $this->salt),          $signing,              true);
        $signing  = hash_hmac('sha256', $msg,                            $signing,              true);

        $body  = pack('n', strlen($this->appId)) . $this->appId;
        $body .= pack('n', strlen($signing))     . $signing;
        $body .= $msg;

        return self::VERSION . base64_encode(gzcompress($body));
    }
}
