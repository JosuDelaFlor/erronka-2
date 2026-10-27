<?php
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['account_action'] ?? '';
    if ($action === 'changepass') {
        $current = (string) ($_POST['currentpass'] ?? '');
        $new = (string) ($_POST['newpass'] ?? '');
        if (strlen($new) < 10 || $new !== (string) ($_POST['confnewpass'] ?? '')) {
            $message = 'La contraseña nueva debe tener 10 caracteres y coincidir.';
        } else {
            $stmt = mysqli_prepare($conx, 'SELECT password FROM users WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$user || !password_verify($current, $user['password'])) {
                $message = 'La contraseña actual no es correcta.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conx, 'UPDATE users SET password = ? WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'si', $hash, $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
                session_unset();
                session_destroy();
                header('Location: index.php?action=login');
                exit;
            }
        }
    } elseif ($action === 'adduser' && !empty($_SESSION['is_admin'])) {
        $newuser = trim((string) ($_POST['newuser'] ?? ''));
        $newpass = (string) ($_POST['newuserpass'] ?? '');
        if (filter_var($newuser, FILTER_VALIDATE_EMAIL) && strlen($newpass) >= 10) {
            $hash = password_hash($newpass, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conx, 'INSERT INTO users (username,password) VALUES (?,?)');
            mysqli_stmt_bind_param($stmt, 'ss', $newuser, $hash);
            mysqli_stmt_execute($stmt);
        }
    } elseif ($action === 'deleteuser' && !empty($_SESSION['is_admin'])) {
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id > 0 && $id !== (int) $_SESSION['user_id']) {
            $stmt = mysqli_prepare($conx, 'DELETE FROM users WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
        }
    }
}
?>
<div align="center">
    <?php if (!empty($message)): ?><p><?php echo e($message); ?></p><?php endif; ?>
    <fieldset style="width:300px;">
        <legend><b>Change Password</b></legend>
        <form action="index.php?action=account" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="account_action" value="changepass">
            Contraseña actual: <input type="password" name="currentpass" required><br>
            Nueva contraseña: <input type="password" name="newpass" required><br>
            Confirmar: <input type="password" name="confnewpass" required><br>
            <input type="submit" value="Change">
        </form>
    </fieldset>
    <?php if (!empty($_SESSION['is_admin'])): ?>
    <fieldset style="width:500px;">
        <legend><b>Administrar usuarios</b></legend>
        <form action="index.php?action=account" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="account_action" value="adduser">
            Email: <input type="email" name="newuser" required>
            Contraseña: <input type="password" name="newuserpass" required>
            <input type="submit" value="Add">
        </form>
        <?php $users = mysqli_query($conx, 'SELECT id, username FROM users ORDER BY username'); ?>
        <?php while ($user = mysqli_fetch_assoc($users)): ?>
            <p><?php echo e($user['username']); ?>
            <?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?>
                <form action="index.php?action=account" method="post" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="account_action" value="deleteuser">
                    <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                    <button type="submit">[delete]</button>
                </form>
            <?php endif; ?></p>
        <?php endwhile; ?>
    </fieldset>
    <?php endif; ?>
</div>
