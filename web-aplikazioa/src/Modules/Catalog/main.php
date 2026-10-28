<?php
$keyword = trim((string) ($_GET['keyword'] ?? ''));
$pattern = '%' . $keyword . '%';
$stmt = mysqli_prepare($conx, 'SELECT id, izena, deskripzioa, salneurria, pic FROM produktuak WHERE izena LIKE ? OR deskripzioa LIKE ?');
mysqli_stmt_bind_param($stmt, 'ss', $pattern, $pattern);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$produktuak = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<?php if (!$produktuak): ?>
    <fieldset style="width:500px;">
        <legend><b>Ez dago produkturik katalogoan <?php echo e($keyword); ?> deitzen denik</b></legend>
        <div align="center"><h3>Enpresaren hasierako orria</h3></div>
    </fieldset>
<?php else: ?>
<table width="1000" cellpadding="10" cellspacing="10" align="center">
<?php foreach ($produktuak as $data): ?>
    <tr>
        <td align="center" valign="top" width="40%">
            <fieldset>
                <?php if (!empty($data['pic'])): ?>
                    <a href="images/<?php echo e(rawurlencode($data['pic'])); ?>">
                        <img src="images/<?php echo e(rawurlencode($data['pic'])); ?>" border="1" alt="">
                    </a>
                <?php endif; ?>
            </fieldset>
        </td>
        <td valign="top" width="60%">
            <fieldset><legend><b>Izena</b></legend><?php echo e($data['izena']); ?> - <?php echo e($data['salneurria']); ?>€</fieldset>
            <fieldset><legend><b>Deskripzioa</b></legend><?php echo nl2br(e($data['deskripzioa'])); ?></fieldset>
            <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="index.php?action=description&amp;pic_id=<?php echo (int) $data['id']; ?>">Deskripzioa/Salneurria aldatu</a>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
