<?php
$message = "";
if (isset($_POST['click_generate'])) {
    $host = 'hm20b06hfn1.sn.mynetname.net';
    $user = 'admin';
    $pass = '06121212';
    $port = 8728;
    $code = "MEO-" . substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);

    $s = @fsockopen($host, $port, $errno, $errstr, 5);
    if (!$s) {
        $message = "<div class='alert error'>Gagal sambung: $errstr</div>";
    } else {
        function hp($s, $t) {
            $l = strlen($t);
            if ($l < 128) fwrite($s, chr($l));
            else { $l |= 0x8000; fwrite($s, chr(($l >> 8) & 0xFF) . chr($l & 0xFF)); }
            fwrite($s, $t);
        }

        hp($s, "/login");
        hp($s, "=name=" . $user);
        hp($s, "=password=" . $pass);
        hp($s, ""); 
        fread($s, 100);

        hp($s, "/ip/hotspot/user/add");
        hp($s, "=name=" . $code);
        hp($s, "=password=" . $code);
        hp($s, "=profile=30m");
        hp($s, "=comment=v1");
        hp($s, "");
        
        $res = fread($s, 200);
        fclose($s);

        if (strpos($res, '!trap') !== false) {
            $message = "<div class='alert error'>MikroTik Tolak (Periksa Profile 30m): " . bin2hex($res) . "</div>";
        } else {
            $message = "<div class='alert success'>
                            <h3>⚡ BAUCAR BERJAYA DIJANA</h3>
                            <div class='voucher-box'>$code</div>
                        </div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; background: #f5f6fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 90%; }
        .btn-generate { background: #e67e22; color: white; border: none; padding: 15px 30px; font-weight: bold; border-radius: 8px; cursor: pointer; width: 100%; font-size: 16px; }
        .alert { margin-top: 20px; padding: 15px; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .voucher-box { font-size: 28px; font-weight: bold; color: #d35400; margin-top: 15px; padding: 10px; border: 2px dashed #e67e22; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Kopimeo x La Carne</h2>
        <form method="POST"><button type="submit" name="click_generate" class="btn-generate">GENERATE VOUCHER</button></form>
        <?php echo $message; ?>
    </div>
</body>
</html>
