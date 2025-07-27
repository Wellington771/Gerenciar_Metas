<?php
// fechar_mes.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/database.php';
$confirmado = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    $pdo->exec('UPDATE Colaboradores SET ValorAtualVendas = 0');
    $confirmado = true;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Fechar Mês</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e8f5e9; }
        .btn-success { background-color: #388e3c; border: none; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card p-4">
                <h4 class="mb-3" style="color:#388e3c;">Fechar Mês</h4>
                <?php if ($confirmado): ?>
                    <div class="alert alert-success">Vendas zeradas com sucesso!</div>
                <?php else: ?>
                    <form method="post">
                        <p>Tem certeza que deseja zerar todas as vendas dos colaboradores?</p>
                        <button type="submit" name="confirmar" class="btn btn-success">Confirmar</button>
                        <a href="dashboard.php" class="btn btn-secondary ms-2">Cancelar</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
