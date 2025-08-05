<?php
// Inicia a sessão para controlar o login do usuário
session_start();

// Se já estiver logado, redireciona automaticamente para o dashboard
if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit;
}

// Variável que armazenará a mensagem de erro, se houver
$erro = '';

// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura os dados do formulário
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Verifica se os dados estão corretos (usuário e senha fixos)
    if ($usuario === 'Geane_Lacerda' && $senha === 'Lacerd@981') {
        // Se estiverem corretos, salva o login na sessão e redireciona
        $_SESSION['usuario'] = $usuario;
        header('Location: dashboard.php');
        exit;
    } else {
        // Se forem inválidos, mostra mensagem de erro
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

    <!-- Importa o Bootstrap 5 para estilização -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos customizados para o layout da tela de login -->
    <style>
        body {
            background: #e8f5e9; /* Fundo verde claro */
        }
        .card {
            border-color: #388e3c; /* Borda verde */
        }
        .btn-success {
            background-color: #388e3c;
            border: none;
        }
    </style>
</head>
<body>

    <!-- Barra de navegação superior com nome do sistema -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #388e3c;">
        <div class="container-fluid px-4">
            <span class="navbar-brand" style="font-size:1.5rem;font-weight:500;">Dashboard Gerencial</span>
        </div>
    </nav>

    <!-- Área central da tela com o formulário de login -->
    <div class="container d-flex justify-content-center align-items-center" style="height:90vh;">
        <div class="card p-4 shadow" style="min-width:350px;">
            <h3 class="mb-3 text-center" style="color:#388e3c;">Dashboard Gerencial</h3>

            <!-- Exibe erro caso o login tenha falhado -->
            <?php if ($erro): ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php endif; ?>

            <!-- Formulário de login -->
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
