<?php

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?action=login');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (empty($_SESSION['is_admin'])) {
        http_response_code(403);
        exit('Acceso denegado.');
    }
}

function save_image_upload(array $file, string $directory): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('La imagen no es válida o supera 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Solo se permiten imágenes JPEG, PNG o GIF.');
    }

    if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $name)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    return $name;
}
