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
        $message = "Gagal sambung: $errstr";
    } else {
        // Fungsi hantar paket ringkas
        function hp($s, $t) {
            $l = strlen($t);
            if ($l < 128) fwrite($s, chr($l));
            else { $l |= 0x8000; fwrite($s, chr(($l >> 8) & 0xFF) . chr($l & 0xFF)); }
            fwrite($s, $t);
        }

        // Login
        hp($s, "/login");
        hp($s, "=name=" . $user);
        hp($s, "=password=" . $pass);
        hp($s, ""); 
        fread($s, 100); // skip respon

        // Tambah User
        hp($s, "/ip/hotspot/user/add");
        hp($s, "=name=" . $code);
        hp($s, "=password=" . $code);
        hp($s, "=profile=30m");
        hp($s, "=comment=v1");
        hp($s, "");
        
        $res = fread($s, 200);
        fclose($s);

        if (strpos($res, '!trap') !== false) {
            $message = "MikroTik Tolak: " . bin2hex($res);
        } else {
            $message = "SUCCESS! Kod: " . $code;
        }
    }
}
?>
<form method="POST"><button type="submit" name="click_generate">GENERATE</button></form>
<p><?php echo $message; ?></p>
