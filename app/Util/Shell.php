<?php

namespace App\Util;

class Shell
{
    private $conn;

    public function __construct(string $ip, string $user, string $password)
    {
        $this->conn = \ssh2_connect($ip);

        if (!$this->conn) {
            throw new \ErrorException('ssh cannot establish a connection');
        }

        $auths = \ssh2_auth_none($this->conn, $user);

        if (!in_array('password', $auths)) {
            throw new \ErrorException('Server not supports password based authentication');
        }

        if (!\ssh2_auth_password($this->conn, $user, $password)) {
            throw new \ErrorException('Wrong username or password, cannot establish ssh connection.');
        }

    }

    public function exec($command)
    {
        $stream = \ssh2_exec($this->conn, $command);

        if (!$stream) {
            throw new \ErrorException('Command execution failed');
        }

        stream_set_blocking($stream, true); // 获取执行pwd后的内容
        $resource = \ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        return stream_get_contents($resource);
    }
}
