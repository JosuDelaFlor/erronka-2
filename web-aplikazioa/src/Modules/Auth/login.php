<?php

if (!empty($_SESSION['user_id'])) {
    echo '<div align="center"><h5>Ya has iniciado sesión.</h5></div>';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $stmt = mysqli_prepare($conx, 'SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['username'] === 'admin@bdweb';
        header('Location: index.php');
        exit;
    }
    $error = 'Credenciales incorrectas.';
}
?>
<div align="center">
    <fieldset style="width:300px;">
        <legend><b>Login</b></legend>
        <?php if (!empty($error)): ?><p><?php echo e($error); ?></p><?php endif; ?>
        <form action="index.php?action=login" method="post">
            <label>Username/Email: <input type="text" name="username" required maxlength="30"></label><br>
            <label>Password: <input type="password" name="password" required></label><br><br>
            <input type="submit" value="Login">
        </form>
    </fieldset>
</div>
