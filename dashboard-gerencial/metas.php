<?php
// metas.php - Tela para definir metas dos colaboradores
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/database.php';
$msg = '';
// Atualiza meta se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['colaborador_id'], $_POST['meta_mensal'])) {
    $colaborador_id = intval($_POST['colaborador_id']);
    $meta_mensal = floatval(str_replace(',', '.', $_POST['meta_mensal']));
    $stmt = $pdo->prepare('UPDATE Colaboradores SET MetaMensal = ? WHERE ColaboradorID = ?');
    $stmt->execute([$meta_mensal, $colaborador_id]);
    $msg = 'Meta atualizada com sucesso!';
}
// Busca colaboradores
$stmt = $pdo->query('SELECT * FROM Colaboradores ORDER BY NomeCompleto');
$colaboradores = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Definir Metas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e8f5e9; }
        .btn-success { background-color: #388e3c; border: none; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h4 class="mt-4" style="color:#388e3c;">Definir Metas dos Colaboradores</h4>
    <?php if ($msg): ?>
        <div class="alert alert-success mt-2"> <?php echo $msg; ?> </div>
    <?php endif; ?>
    <table class="table table-bordered table-success mt-3">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Código</th>
                <th>Meta Mensal (R$)</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($colaboradores as $c): ?>
            <tr>
                <form method="post">
                    <td><?php echo htmlspecialchars($c['NomeCompleto']); ?></td>
                    <td><?php echo htmlspecialchars($c['CodigoExterno']); ?></td>
                    <td>
                        <input type="number" step="0.01" name="meta_mensal" value="<?php echo number_format($c['MetaMensal'],2,'.',''); ?>" class="form-control" required>
                        <input type="hidden" name="colaborador_id" value="<?php echo $c['ColaboradorID']; ?>">
                    </td>
                    <td>
                        <button type="submit" class="btn btn-success">Salvar</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
