<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerConfig extends Model
{
    public const KuKuBao = 10001;
    public const CuiHua = 10003;
    public const YouCaihua = 10005;
    public const XinFeng = 10002;
    public const BaDa = 10004;
    public const ShiRuan = 10006;
    public const HanXuan = 10007;
    public const TangMu = 10008;
    public const ZiRan = 10009;
    public const Example = 9000;

    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'web_server_config';

    protected $connection = 'central';

    protected $fillable = [
        'code', 'db_host', 'db_name', 'db_user', 'db_password', 'db_port', 'game_port', 'game_ip',
        'server_name', 'stream_url', 'mch_id', 'agent_type', 'platform_type', 'expired_at', 'merge_room', 'created_at'
    ];

    protected $primaryKey = 'server_no';

    public function isCentralServer(): bool
    {
        return $this->server_no == config('app.central_server_id');
    }

    public function isExpired(): bool
    {
        $expiredAt = (int)$this->expired_at;
        if ($expiredAt === 0) {
            $isExpired = false;
        } else if (time() >= $expiredAt) {
            $isExpired = true;
        } else {
            $isExpired = false;
        }
        return $isExpired;
    }
}
