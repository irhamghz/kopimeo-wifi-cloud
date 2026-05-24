<?php
// 1. Panggil library API MikroTik (Fail wajib ada sekali)
require('routeros_api.class.php');

$message = "";

if (isset($_POST['click_generate'])) {
    
    $api = new RouterosAPI();

    // -------------------------------------------------------------
    // KONFIGURASI ROUTER MIKROTIK KAFE (VERSI DEKAT HOSTING)
    // -------------------------------------------------------------
    $mikrotik_host     = 'hm20b06hfn1.sn.mynetname.net'; // <-- MASUKKAN DNS NAME MIKROTIK ANDA (Ambil dari IP > Cloud)
    $mikrotik_user     = 'admin';                     // Username Winbox anda
    $mikrotik_password = '06121212';               // Password Winbox anda
    // -------------------------------------------------------------

    // 2. Skrip PHP menjana Kod Baucar Rawak 4-Digit (Contoh: MEO-9X2A)
    $char = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $generated_code = "MEO-" . substr(str_shuffle($char), 0, 4);

    // 3. Hosting hubungi MikroTik di kafe merentasi internet
    if ($api->connect($mikrotik_host, $mikrotik_user, $mikrotik_password)) {
        
        // Memasukkan baucar baharu terus ke dalam senarai IP Hotspot Users
        $api->comm("/ip/hotspot/user/add", array(
            "server"   => "all",
            "name"     => $generated_code,       // Username WiFi
            "password" => $generated_code,       // Password WiFi
            "profile"  => "30m",     // Nama Profile di Winbox (Mesti ada Session Timeout 30m)
            "comment"  => "Dijana oleh Staf melalui Web Host"
        ));

        $api->disconnect();
        
        // Paparkan kod baucar pada skrin hosting untuk staf
        $message = "<div class='alert success'>
                        <h3>⚡ BAUCAR BERJAYA DIJANA </h3>
                        <div class='voucher-box'>$generated_code</div>
                    </div>";
    } else {
        $message = "<div class='alert error'>❌ Gagal tembus ke MikroTik kafe. Periksa sama ada internet kafe hidup atau DDNS betul.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopimeo x La Carne - Cloud Panel Staf</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f6fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); text-align: center; max-width: 400px; width: 100%; }
        h2 { color: #2f3640; margin-bottom: 5px; }
        p { color: #718093; margin-bottom: 30px; font-size: 14px; }
        .btn-generate { background-color: #e67e22; color: white; border: none; padding: 16px 32px; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; transition: 0.2s; width: 100%; box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3); }
        .btn-generate:hover { background-color: #d35400; }
        .alert { margin-top: 20px; padding: 15px; border-radius: 6px; font-size: 14px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .voucher-box { font-size: 32px; font-weight: bold; color: #d35400; letter-spacing: 2px; margin: 10px 0; background: #fff; padding: 12px; border: 2px dashed #e67e22; border-radius: 6px; display: inline-block; width: 80%; }
    </style>
</head>
<body>

<div class="container">
    <h2>Kopimeo x La Carne</h2>
    <p>Sistem WiFi Baucar (Web Host Panel Staf)</p>
    
    <?php echo $message; ?>

    <form method="POST" action="">
        <button type="submit" name="click_generate" class="btn-generate">
            JANA BAUCAR 30 MINIT
        </button>
    </form>
</div>

</body>
</html>