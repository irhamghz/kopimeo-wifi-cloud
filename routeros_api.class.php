<?php
class RouterosAPI {
    var $debug = false;
    var $error_no;
    var $error_str;
    var $attempts = 5;
    var $delay = 2;
    var $port = 8728;
    var $timeout = 3;
    var $socket;
    var $connected = false;

    function connect($host, $username, $password) {
        for ($attempts = 1; $attempts <= $this->attempts; $attempts++) {
            $this->socket = @fsockopen($host, $this->port, $this->error_no, $this->error_str, $this->timeout);
            if ($this->socket) {
                socket_set_timeout($this->socket, $this->timeout);
                if ($this->login($username, $password)) {
                    $this->connected = true;
                    return true;
                }
                fclose($this->socket);
            }
            sleep($this->delay);
        }
        return false;
    }

    function disconnect() {
        fclose($this->socket);
        $this->connected = false;
    }

    function comm($com, $arr = array()) {
        $count = count($arr);
        $this->write($com, $count > 0 ? false : true);
        $i = 0;
        if ($count > 0) {
            foreach ($arr as $k => $v) {
                $i++;
                $this->write('=' . $k . '=' . $v, $i == $count ? true : false);
            }
        }
        $RESPONSE = array();
        $is_reply = true;
        while ($is_reply) {
            $REPLY = $this->read();
            if ($REPLY[0] == '!re') {
                $item = array();
                foreach ($REPLY as $p) {
                    if (preg_match('/^=([^=]+)=(.*)$/', $p, $matches)) {
                        $item[$matches[1]] = $matches[2];
                    }
                }
                $RESPONSE[] = $item;
            } elseif ($REPLY[0] == '!trap') {
                $item = array();
                foreach ($REPLY as $p) {
                    if (preg_match('/^=([^=]+)=(.*)$/', $p, $matches)) {
                        $item[$matches[1]] = $matches[2];
                    }
                }
                $RESPONSE[] = $item;
            } elseif ($REPLY[0] == '!done') {
                $is_reply = false;
            }
        }
        return $RESPONSE;
    }

    function write($command, $tag = true) {
        $l = strlen($command);
        if ($l < 0x80) {
            fwrite($this->socket, chr($l));
        } elseif ($l < 0x4000) {
            $l |= 0x8000;
            fwrite($this->socket, chr(($l >> 8) & 0xFF) . chr($l & 0xFF));
        } elseif ($l < 0x200000) {
            $l |= 0xC00000;
            fwrite($this->socket, chr(($l >> 16) & 0xFF) . chr(($l >> 8) & 0xFF) . chr($l & 0xFF));
        }
        fwrite($this->socket, $command);
        if ($tag) {
            fwrite($this->socket, chr(0));
        }
    }

    function read() {
        $RESPONSE = array();
        $received = '';
        while (true) {
            $clength = ord(fgetc($this->socket));
            if ($clength == 0) {
                break;
            }
            $response_line = '';
            $to_read = $clength;
            while ($to_read > 0) {
                $chunk = fread($this->socket, $to_read);
                $response_line .= $chunk;
                $to_read -= strlen($chunk);
            }
            $RESPONSE[] = $response_line;
        }
        return $RESPONSE;
    }

    function login($username, $password) {
        $this->write('/login', false);
        $this->write('=name=' . $username, true);
        $RESPONSE = $this->read();
        if ($RESPONSE[0] == '!done') {
            if (isset($RESPONSE[1]) && preg_match('/^=ret=(.*)$/', $RESPONSE[1], $matches)) {
                $this->write('/login', false);
                $this->write('=name=' . $username, false);
                $this->write('=response=00' . md5(chr(0) . $password . pack('H*', $matches[1])), true);
                $RESPONSE = $this->read();
                if ($RESPONSE[0] == '!done') {
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }
}
?>
