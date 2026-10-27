<?php
require_admin();
$id = filter_input(INPUT_GET, 'pic_id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Producto no válido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $description = trim((string) ($_POST['deskripzioa'] ?? ''));
    $price = filter_input(INPUT_POST, 'salneurria', FILTER_VALIDATE_FLOAT);
    if ($price === false || $price < 0 || strlen($description) > 150) {
        exit('Datos no válidos.');
    }
    $stmt = mysqli_prepare($conx, 'UPDATE produktuak SET deskripzioa = ?, salneurria = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'sdi', $description, $price, $id);
    mysqli_stmt_execute($stmt);
    header('Location: index.php');
    exit;
}
$stmt = mysqli_prepare($conx, 'SELECT izena, deskripzioa, salneurria, pic FROM produktuak WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$data) {
    exit('Producto no encontrado.');
}
?>
<div align="center"><fieldset style="width:300px;">
<legend><b>Descripción</b></legend>
<h4><?php echo e($data['izena']); ?> - <?php echo e($data['salneurria']); ?>€</h4>
<?php if (!empty($data['pic'])): ?><img src="images/<?php echo e(rawurlencode($data['pic'])); ?>" border="1" alt=""><?php endif; ?>
<form action="index.php?action=description&amp;pic_id=<?php echo $id; ?>" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <textarea name="deskripzioa" cols="50" rows="10"><?php echo e($data['deskripzioa']); ?></textarea><br>
    Salneurri berria: <input type="number" step="0.01" min="0" name="salneurria" value="<?php echo e($data['salneurria']); ?>">
    <input type="submit" value="Aldatu">
</form>
</fieldset></div>
