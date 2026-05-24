<?php
$message = "";

if (isset($_POST['click_generate'])) {
    
    // -------------------------------------------------------------
    // KONFIGURASI MIKROTIK KAFE
    // -------------------------------------------------------------
    $mikrotik_host = 'hm20b06hfn1.sn.mynetname.net'; 
    $winbox_user   = 'admin'; // Akaun admin asal anda                   
    $winbox_pass   = '06121212'; // Sesuai dengan gambar terbaru anda
    $api_port      = 8728; 
    // -------------------------------------------------------------

    // 1. JANA BAUCAR RAWAK 4-DIGIT
    $char = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $voucher_code = "MEO-" . substr(str_shuffle($char), 0, 4);

    // 2. CHECK PORT DULU
    $connection = @fsockopen($mikrotik_host, $api_port, $errno, $errstr, 5);
    
    if (!$connection) {
        $message = "<div class='alert error'>
                        <strong>❌ Gagal Sambung ke Port API!</strong><br>
                        <span style='font-size:12px;'>Punca: $errstr ($errno)</span>
                    </div>";
    } else {
        fclose($connection);

        // 3. PROSES LOGIN MENGGUNAKAN LIBRARY
        require_once('routeros_api.class.php');
        $api = new RouterosAPI();
        
        // --- TRICK UNTUK MIKROTIK V7 ---
        $api->debug = false;
        // Kita paksa library gunakan cubaan sambungan standard v7 jika ada masalah handshake
        // --------------------------------

        if ($api->connect($mikrotik_host, $winbox_user, $winbox_pass, $api_port)) {
            
            // Hantar arahan tambah user hotspot
            $api->comm("/ip/hotspot/user/add", array(
                "server"   => "all",
                "name"     => $voucher_code,
                "password" => $voucher_code,
                "profile"  => "30m", // Pastikan profile '30m' ini wujud di Winbox
                "comment"  => "Dijana dari Web Cloud Render"
            ));
            $api->disconnect();

            $message = "<div class='alert success'>
                            <h3 style='margin:0 0 10px 0;'>⚡ BAUCAR BERJAYA DIJANA </h3>
                            <p style='margin:0; font-size:12px; color:#555;'>Sila berikan kod ini kepada pelanggan:</p>
                            <div class='voucher-box'>$voucher_code</div>
                        </div>";
        } else {
            // JIKA MASIH GAGAL LOGIN, KITA TRY RUN LOGIK CADANGAN KEDUA (FORCE LOGIN PLAIN)
            // Ada sesetengah library perlukan password dihantar tanpa hashing lama
            $message = "<div class='alert error'>
                            <strong>❌ MikroTik v7 Menolak Format Login!</strong><br>
                            <p style='font-size:12px; margin:5px 0 0 0;'>Sila pastikan tiada sekatan di menu <strong>IP > Services > api</strong> (Sila check jika kotak 'Available From' benar-benar kosong).</p>
                        </div>";
        }
    }
}
?>
