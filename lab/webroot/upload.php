<?php
// Upload endpoint — does NOT call session_start() in script code.
// upload_progress rfc1867 handler opens the session automatically when it
// sees PHP_SESSION_UPLOAD_PROGRESS in a multipart POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = $_FILES['replay'] ?? null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $dest = '/var/www/html/uploads/' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $dest);
        session_start();
        $_SESSION['flash'] = 'Replay <strong>' . htmlspecialchars(basename($file['name'])) . '</strong> enviado com sucesso.';
        header('Location: /index.php');
        exit;
    } else {
        session_start();
        $_SESSION['flash_err'] = 'Nenhum arquivo selecionado ou erro no envio (código ' . ($file['error'] ?? 'N/A') . ').';
        header('Location: /index.php');
        exit;
    }
} else {
    http_response_code(405);
    echo "POST only";
}
