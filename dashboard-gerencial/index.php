<?php
// index.php - Login
session_start();
if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit;
}
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';
    if ($usuario === 'Geane_Lacerda' && $senha === 'Lacerd@981') {
        $_SESSION['usuario'] = $usuario;
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = 'Usuário ou senha inválidos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard Gerencial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e8f5e9; }
        .card { border-color: #388e3c; }
        .btn-success { background-color: #388e3c; border: none; }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="height:100vh;">
        <div class="card p-4 shadow" style="min-width:350px;">
            <h3 class="mb-3 text-center" style="color:#388e3c;">Dashboard Gerencial</h3>
            <?php if ($erro): ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>
