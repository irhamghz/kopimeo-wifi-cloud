<?php
$message = "";

if (isset($_POST['click_generate'])) {
    
    // -------------------------------------------------------------
    // KONFIGURASI MIKROTIK KAFE
    // -------------------------------------------------------------
    $mikrotik_host = 'hm20b06hfn1.sn.mynetname.net'; 
    $winbox_user   = 'admin';                    
    $winbox_pass   = '06121212'; 
    $api_port      = 8728; 
    // -------------------------------------------------------------

    // 1. JANA BAUCAR RAWAK 4-DIGIT
    $char = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $voucher_code = "MEO-" . substr(str_shuffle($char), 0, 4);

    // 2. SAMBUNG KE MIKROTIK GUNAKAN SOKET NATIVE
    $socket = @fsockopen($mikrotik_host, $api_port, $errno, $errstr, 5);
    
    if (!$socket) {
        $message = "<div class='alert error'>
                        <strong>❌ Gagal Sambung ke Port API!</strong><br>
                        <span style='font-size:12px;'>Punca: $errstr ($errno)</span>
                    </div>";
    } else {
        // Fungsi pembantu untuk hantar data ke MikroTik API
        function send_packet($socket, $text) {
            $length = strlen($text);
            if ($length < 0x80) {
                fwrite($socket, chr($length));
            } elseif ($length < 0x4000) {
                $length |= 0x8000;
                fwrite($socket, chr(($length >> 8) & 0xFF) . chr($length & 0xFF));
            }
            fwrite($socket, $text);
        }

        // Fungsi untuk baca respon dari MikroTik
        function read_packet($socket) {
            $byte = ord(fread($socket, 1));
            $length = 0;
            if (($byte & 0x80) == 0x00) {
                $length = $byte;
            } elseif (($byte & 0xC0) == 0x80) {
                $byte2 = ord(fread($socket, 1));
                $length = (($byte & 0x3F) << 8) + $byte2;
            }
            return ($length > 0) ? fread($socket, $length) : "";
        }

        // --- PROSES BERSALAMAN / LOGIN (ROUTEROS V7 COMPATIBLE) ---
        send_packet($socket, "/login");
        read_packet($socket); // Baca respon kosong pertama
        
        send_packet($socket, "=name=" . $winbox_user);
        send_packet($socket, "=password=" . $winbox_pass);
        send_packet($socket, ""); // Tamat paket login

        $status = read_packet($socket);

        // Jika MikroTik v7 terima login, dia akan balas !done
        if (strpos($status, '!done') !== false) {
            
            // --- PROSES TAMBAH USER HOTSPOT ---
            send_packet($socket, "/ip/hotspot/user/add");
            send_packet($socket, "=server=all");
            send_packet($socket, "=name=" . $voucher_code);
            send_packet($socket, "=password=" . $voucher_code);
            send_packet($socket, "=profile=30m"); // Pastikan profile '30m' ada dalam Winbox
            send_packet($socket, "=comment=Dijana dari Web Cloud Render");
            send_packet($socket, ""); // Tamat paket hantar data
            
            // Baca maklumbalas dari MikroTik
            $result = read_packet($socket);
            fclose($socket);

            $message = "<div class='alert success'>
                            <h3 style='margin:0 0 10px 0;'>⚡ BAUCAR BERJAYA DIJANA </h3>
                            <p style='margin:0; font-size:12px; color:#555;'>Sila berikan kod ini kepada pelanggan:</p>
                            <div class='voucher-box'>$voucher_code</div>
                        </div>";
        } else {
            fclose($socket);
            $message = "<div class='alert error'>
                            <strong>❌ MikroTik Menolak Login!</strong><br>
                            <p style='font-size:12px; margin:5px 0 0 0;'>Sila pastikan Password <strong>'0121212'</strong> untuk user <strong>'admin'</strong> adalah betul dalam Winbox.</p>
                        </div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopimeo x La Carne - WiFi Panel</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f6fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); text-align: center; max-width: 400px; width: 100%; }
        .btn-generate { background-color: #e67e22; color: white; border: none; padding: 16px 32px; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; width: 100%; box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3); }
        .btn-generate:hover { background-color: #d35400; }
        .alert { margin-top: 20px; padding: 15px; border-radius: 8px; font-size: 14px; text-align: left; line-height: 1.5; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; text-align: center; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .voucher-box { font-size: 32px; font-weight: bold; color: #d35400; letter-spacing: 2px; margin: 15px 0 0 0; background: #fff; padding: 12px; border: 2px dashed #e67e22; border-radius: 6px; display: inline-block; width: 80%; }
    </style>
</head>
<body>

<div class="container">
    <h2>Kopimeo x La Carne</h2>
    <p>Panel WiFi Staf (Cloud Version)</p>
    
    <?php echo $message; ?>

    <form method="POST" action="">
        <button type="submit" name="click_generate" class="btn-generate">
            GENERATE VOUCHER
        </button>
    </form>
</div>

</body>
</html>
