<?php
// excluir_colaborador.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/database.php';

$msg = '';
$colaboradores = [];

// Buscar todos os colaboradores para exibir na lista
$stmt = $pdo->query("SELECT CodigoExterno, NomeCompleto FROM Colaboradores ORDER BY NomeCompleto");
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo'])) {
    $codigo = trim($_POST['codigo']);
    if ($codigo) {
        $stmt = $pdo->prepare("DELETE FROM Colaboradores WHERE CodigoExterno = ?");
        if ($stmt->execute([$codigo])) {
            if ($stmt->rowCount() > 0) {
                $msg = "✅ Colaborador excluído com sucesso!";
            } else {
                $msg = "⚠ Nenhum colaborador encontrado com este código.";
            }
        } else {
            $msg = "❌ Erro ao excluir colaborador.";
        }
    } else {
        $msg = "⚠ Selecione um colaborador.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Excluir Colaborador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4fdf7; }
        .btn-danger { background-color: #d32f2f; border: none; }
        .btn-secondary { background-color: #8d8d8d; border: none; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container mt-5" style="max-width: 600px;">
    <h4 class="mb-4 text-danger">Excluir Colaborador</h4>

    <?php if ($msg): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <form method="post" onsubmit="return confirm('Tem certeza que deseja excluir este colaborador?')">
        <div class="mb-3">
            <label for="codigo" class="form-label">Selecione o Colaborador</label>
            <select name="codigo" id="codigo" class="form-select" required>
                <option value="">-- Escolha um colaborador --</option>
                <?php foreach ($colaboradores as $col): ?>
                    <option value="<?php echo htmlspecialchars($col['CodigoExterno']); ?>">
                        <?php echo htmlspecialchars($col['NomeCompleto'] . " (Código: " . $col['CodigoExterno'] . ")"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <a href="dashboard.php" class="btn btn-secondary px-4">Voltar ao Início</a>
            <button type="submit" class="btn btn-danger px-4">Excluir</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
