<?php
<?php
session_start();

if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

// Configuração do banco de dados
$host = 'localhost';
$usuario_db = 'root'; // Altere conforme seu ambiente
$senha_db = '';       // Altere conforme seu ambiente
$banco = 'gerenciadormetasdb';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Conecta ao banco
    $conn = new mysqli($host, $usuario_db, $senha_db, $banco);
    if ($conn->connect_error) {
        die('Erro de conexão: ' . $conn->connect_error);
    }

    // Busca o usuário na tabela colaboradores
    $stmt = $conn->prepare("SELECT ColaboradorID, Email, SenhaHash, NivelAcesso, Ativo FROM colaboradores WHERE Email = ? AND Ativo = 1");
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verifica a senha (ajuste conforme o hash usado no cadastro)
        if (password_verify($senha, $row['SenhaHash'])) {
            $_SESSION['usuario'] = $row['Email'];
            $_SESSION['nivel_acesso'] = $row['NivelAcesso'];
            $_SESSION['colaborador_id'] = $row['ColaboradorID'];
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'Usuário ou senha inválidos!';
        }
    } else {
        $erro = 'Usuário ou senha inválidos!';
    }

    $stmt->close();
    $conn->close();
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
