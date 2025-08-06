<?php
// adicionar_colaborador.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/database.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $codigo = trim($_POST['codigo']);

    if ($nome && $codigo) {
        $stmt = $pdo->prepare("INSERT INTO Colaboradores (NomeCompleto, CodigoExterno) VALUES (?, ?)");
        if ($stmt->execute([$nome, $codigo])) {
            $msg = "Colaborador adicionado com sucesso!";
        } else {
            $msg = "Erro ao adicionar colaborador.";
        }
    } else {
        $msg = "Preencha todos os campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Colaborador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4fdf7; }
        .btn-primary { background-color: #388e3c; border: none; }
        .btn-secondary { background-color: #8d8d8d; border: none; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container mt-5" style="max-width: 600px;">
    <h4 class="mb-4 text-success">Adicionar Novo Colaborador</h4>

    <?php if ($msg): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome Completo</label>
            <input type="text" name="nome" id="nome" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="codigo" class="form-label">Código Externo</label>
            <input type="text" name="codigo" id="codigo" class="form-control" required>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <a href="dashboard.php" class="btn btn-secondary px-4">Voltar ao Início</a>
            <button type="submit" class="btn btn-primary px-4">Adicionar</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
