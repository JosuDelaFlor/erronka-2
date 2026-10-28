<?php
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        $stmt = mysqli_prepare($conx, 'SELECT pic FROM produktuak WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $stmt = mysqli_prepare($conx, 'DELETE FROM produktuak WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        if ($product && !empty($product['pic'])) {
            $path = __DIR__ . '/../../../images/' . basename($product['pic']);
            if (is_file($path)) {
                unlink($path);
            }
        }
    } elseif (isset($_POST['upload'])) {
        $name = trim((string) ($_POST['izena'] ?? ''));
        $description = trim((string) ($_POST['deskripzioa'] ?? ''));
        $price = filter_input(INPUT_POST, 'salneurria', FILTER_VALIDATE_FLOAT);
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
        if ($name === '' || strlen($description) > 150 || $price === false || $stock === false || $price < 0 || $stock < 0) {
            exit('Datos de producto no válidos.');
        }
        $image = save_image_upload($_FILES['upfile'] ?? [], __DIR__ . '/../../../images');
        $stmt = mysqli_prepare($conx, 'INSERT INTO produktuak (izena, deskripzioa, salneurria, pic, stock) VALUES (?,?,?,?,?)');
        mysqli_stmt_bind_param($stmt, 'ssdsi', $name, $description, $price, $image, $stock);
        mysqli_stmt_execute($stmt);
    }
}
$products = mysqli_query($conx, 'SELECT id, izena, pic FROM produktuak ORDER BY id');
?>
<div align="center"><table width="1000" cellpadding="10"><tr><td>
<fieldset style="width:300px;"><legend><b>Produktu berria</b></legend>
<form enctype="multipart/form-data" action="index.php?action=updel" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="upload" value="1">
    Izena: <input type="text" name="izena" required><br>
    Deskripzioa: <input type="text" name="deskripzioa"><br>
    Salneurria: <input type="number" step="0.01" min="0" name="salneurria" required><br>
    Stock: <input type="number" min="0" name="stock" required><br>
    Irudia: <input name="upfile" type="file" accept="image/jpeg,image/png,image/gif"><br>
    <input type="submit" value="Igo">
</form></fieldset>
</td><td><fieldset style="width:300px;"><legend><b>Ezabatu</b></legend>
<?php while ($product = mysqli_fetch_assoc($products)): ?>
    <p><?php echo e($product['izena']); ?>
    <form action="index.php?action=updel" method="post" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="delete_id" value="<?php echo (int) $product['id']; ?>">
        <button type="submit">[delete]</button>
    </form></p>
<?php endwhile; ?>
</fieldset></td></tr></table></div>
