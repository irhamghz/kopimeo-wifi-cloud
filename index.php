<?php
require('routeros_api.class.php');

$message = "";

if (isset($_POST['click_generate'])) {
    
    $api = new RouterosAPI();

    // -------------------------------------------------------------
    // KONFIGURASI MIKROTIK KAFE
    // -------------------------------------------------------------
    $mikrotik_host      = 'hm20b06hfn1.sn.mynetname.net'; // <-- Ganti DNS Name anda
    $winbox_user        = 'webcloud';                     
    $winbox_pass        = '123456';               
    // -------------------------------------------------------------

    // 1. KOD PHP JANA BAUCAR RAWAK 4-DIGIT SECARA AUTOMATIK
    $char = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $voucher_code = "MEO-" . substr(str_shuffle($char), 0, 4);

    if ($api->connect($mikrotik_host, $winbox_user, $winbox_pass)) {
        
        // 2. HANTAR MASUK TERUS KE USER HOTSPOT MIKROTIK
        $api->comm("/ip/hotspot/user/add", array(
            "server"   => "all",
            "name"     => $voucher_code,       // Username WiFi
            "password" => $voucher_code,       // Password WiFi (Disamakan senang customer nak taip)
            "profile"  => "30m",     // Mesti sama nama Profile di Winbox
            "comment"  => "Dijana dari Web Cloud Render"
        ));

        $api->disconnect();
        
        // 3. PAPARKAN USERNAME & PASSWORD DEKAT WEB STAF
        $message = "<div class='alert success'>
                        <h3 style='margin:0 0 10px 0;'>⚡ BAUCAR BERJAYA DIJANA </h3>
                        <p style='margin:0; font-size:12px; color:#555;'>Sila berikan kod ini kepada pelanggan:</p>
                        <div class='voucher-box'>$voucher_code</div>
                    </div>";
    } else {
        $message = "<div class='alert error'>❌ Gagal connect ke MikroTik kafe.</div>";
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
        .alert { margin-top: 20px; padding: 20px; border-radius: 8px; font-size: 14px; text-align: center; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; }
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
