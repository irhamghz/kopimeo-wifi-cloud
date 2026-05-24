<?php
$message = "";

if (isset($_POST['click_generate'])) {
    
    $mikrotik_host = 'hm20b06hfn1.sn.mynetname.net'; 
    $winbox_user   = 'admin';                    
    $winbox_pass   = '06121212'; 
    $api_port      = 8728; 

    $char = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $voucher_code = "MEO-" . substr(str_shuffle($char), 0, 4);

    $socket = @fsockopen($mikrotik_host, $api_port, $errno, $errstr, 5);
    
    if (!$socket) {
        $message = "<div class='alert error'><strong>❌ Gagal Sambung Port!</strong><br>$errstr ($errno)</div>";
    } else {
        function send_packet($socket, $text) {
            $length = strlen($text);
            if ($length < 0x80) { fwrite($socket, chr($length)); } 
            elseif ($length < 0x4000) { $length |= 0x8000; fwrite($socket, chr(($length >> 8) & 0xFF) . chr($length & 0xFF)); }
            fwrite($socket, $text);
        }

        function read_packet($socket) {
            $byte = ord(fread($socket, 1));
            $length = ($byte & 0x80) == 0x00 ? $byte : (($byte & 0x3F) << 8) + ord(fread($socket, 1));
            return ($length > 0) ? fread($socket, $length) : "";
        }

        // LOGIN
        send_packet($socket, "/login");
        read_packet($socket); 
        send_packet($socket, "=name=" . $winbox_user);
        send_packet($socket, "=password=" . $winbox_pass);
        send_packet($socket, ""); 

        if (strpos(read_packet($socket), '!done') !== false) {
            
            // TAMBAH USER
            send_packet($socket, "/ip/hotspot/user/add");
            send_packet($socket, "=name=" . $voucher_code);
            send_packet($socket, "=password=" . $voucher_code);
            send_packet($socket, "=profile=30m"); 
            send_packet($socket, "=disabled=no");
            send_packet($socket, "=comment=Auto-Generated");
            send_packet($socket, ""); 
            
            // BACA RESPONS MIKROTIK
            $res1 = read_packet($socket); // !re atau !trap
            $res2 = read_packet($socket); // !done atau error message
            
            fclose($socket);

            if (strpos($res1, '!trap') !== false || strpos($res2, '!trap') !== false) {
                $message = "<div class='alert error'><strong>❌ MikroTik Tolak Arahan!</strong><br>Respon: " . htmlspecialchars($res1 . " " . $res2) . "</div>";
            } else {
                $message = "<div class='alert success'><h3>⚡ BERJAYA!</h3><div class='voucher-box'>$voucher_code</div></div>";
            }
        } else {
            fclose($socket);
            $message = "<div class='alert error'><strong>❌ Gagal Login API</strong></div>";
        }
    }
}
?>
