<?php
$data = $_POST['data'] ?? [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $password2 = (string) ($data['password2'] ?? '');
    if (!filter_var($username, FILTER_VALIDATE_EMAIL) || strlen($password) < 10 || $password !== $password2) {
        $error = 'Introduce un email válido y una contraseña de al menos 10 caracteres que coincida.';
    } else {
        try {
            $image = save_image_upload($_FILES['imagen'] ?? [], __DIR__ . '/../../../uploads');
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conx, 'INSERT INTO users (username,password,izena,abizena,hiria,lurraldea,herrialdea,postakodea,telefonoa,irudia) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $firstname = trim((string) ($data['firstname'] ?? ''));
            $lastname = trim((string) ($data['lastname'] ?? ''));
            $city = trim((string) ($data['city'] ?? ''));
            $state = trim((string) ($data['stateProv'] ?? ''));
            $country = trim((string) ($data['country'] ?? ''));
            $postcode = (int) ($data['postcode'] ?? 0);
            $telephone = (int) ($data['telephone'] ?? 0);
            mysqli_stmt_bind_param(
                $stmt,
                'sssssssiis',
                $username,
                $hash,
                $firstname,
                $lastname,
                $city,
                $state,
                $country,
                $postcode,
                $telephone,
                $image
            );
            mysqli_stmt_execute($stmt);
            header('Location: index.php?action=login');
            exit;
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $error = 'No se pudo completar el registro.';
        }
    }
}
?>
<div class="content">
    <h2>Erregistroa egin</h2>
    <?php if ($error): ?><p><?php echo e($error); ?></p><?php endif; ?>
    <form action="index.php?action=register" method="post" enctype="multipart/form-data">
        <?php
        $fields = ['email' => 'Email/username', 'firstname' => 'Izena', 'lastname' => 'Abizena',
            'city' => 'Hiria', 'stateProv' => 'Lurraldea', 'country' => 'Herrialdea',
            'postcode' => 'Postakodea', 'telephone' => 'Telefonoa'];
        foreach ($fields as $name => $label):
        ?>
            <p><label><?php echo e($label); ?>:
                <input type="text" name="data[<?php echo e($name); ?>]" value="<?php echo e($data[$name] ?? ''); ?>" maxlength="60">
            </label></p>
        <?php endforeach; ?>
        <p><label>Pasahitza: <input type="password" name="data[password]" required></label></p>
        <p><label>Pasahitza errepikatu: <input type="password" name="data[password2]" required></label></p>
        <p><label>Irudia: <input name="imagen" type="file" accept="image/jpeg,image/png,image/gif"></label></p>
        <input type="submit" value="Submit">
    </form>
</div>
