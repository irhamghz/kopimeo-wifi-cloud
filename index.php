<?php
$message = "";

if (isset($_POST['click_generate'])) {
    
    // -------------------------------------------------------------
    // KONFIGURASI MIKROTIK KAFE (PORT FORWARDING ROUTER KEDAI)
    // -------------------------------------------------------------
    $mikrotik_host = 'hm20b06hfn1.sn.mynetname.net'; 
    $winbox_user   = 'webcloud';                    
    $winbox_pass   = '123456'; 
    $api_port      = 8728; // Port asal API MikroTik
    // -------------------------------------------------------------

    // 1. JANA BAUCAR RAWAK 4-DIGIT
    $char = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $voucher_code = "MEO-" . substr(str_shuffle($char), 0, 4);

    // 2. TEST SAMBUNGAN PORT SECARA NATIVE (MENGGUNAKAN FSOCKOPEN)
    $connection = @fsockopen($mikrotik_host, $api_port, $errno, $errstr, 5);
    
    if (!$connection) {
        // Jika sangkut dekat Router Kedai / Firewall MikroTik, ralat ini akan keluar:
        $message = "<div class='alert error'>
                        <strong>❌ Gagal Sambung ke Port API!</strong><br>
                        <span style='font-size:12px;'>Punca: $errstr ($errno)</span><br>
                        <p style='font-size:11px; margin:5px 0 0 0; color:#555;'>Maksudnya: Isyarat dari Render langsung tak lepas masuk ke MikroTik. Sila check balik Port Forwarding Unifi atau Firewall Filter MikroTik.</p>
                    </div>";
    } else {
        fclose($connection);

        // 3. JALANKAN PROSES LOGIN & INPUT DATA JIKA PORT TERBUKA
        require_once('routeros_api.class.php');
        $api = new RouterosAPI();
        $api->debug = false; 

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
            // Jika port terbuka tapi salah Username/Password atau isu akaun MikroTik:
            $message = "<div class='alert error'>
                            <strong>❌ Port Terbuka, Tapi Gagal Login!</strong><br>
                            <p style='font-size:12px; margin:5px 0 0 0;'>Maksudnya: Port Forwarding dah menjadi, tapi Username <strong>'$winbox_user'</strong> atau Password <strong>'$winbox_pass'</strong> salah di dalam Winbox MikroTik (System > Users).</p>
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
