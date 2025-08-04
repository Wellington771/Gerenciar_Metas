<?php
session_start();
require_once 'config/database.php';

function moedaParaFloatPHP($valor) {
    $valor = str_replace('.', '', $valor);
    $valor = str_replace(',', '.', $valor);
    return floatval($valor);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['colaboradores'])) {
    foreach ($_POST['colaboradores'] as $id => $dados) {
        $maquiagem = moedaParaFloatPHP($dados['meta_maquiagem']);
        $skincare = moedaParaFloatPHP($dados['meta_skincare']);
        $produtosGerais = moedaParaFloatPHP($dados['meta_produtos_gerais']);
        $metaTotal = $maquiagem + $skincare + $produtosGerais;

        $stmt = $pdo->prepare("UPDATE Colaboradores 
            SET MetaMaquiagem = ?, MetaSkinCare = ?, MetaProdutosGerais = ?, MetaMensal = ? 
            WHERE ColaboradorID = ?");
        $stmt->execute([$maquiagem, $skincare, $produtosGerais, $metaTotal, $id]);
    }

    header("Location: dashboard.php");
    exit;
}

$colaboradores = $pdo->query("SELECT * FROM Colaboradores ORDER BY NomeCompleto")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Definir Metas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4fdf7; }
        .table-success td { vertical-align: middle; }
        input[readonly] { background-color: #d0f0d0; font-weight: bold; }
        .btn-primary { background-color: #388e3c; border: none; }
        .btn-secondary { background-color: #8d8d8d; border: none; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h4 class="mb-4" style="color:#2e7d32;">Definir Metas dos Colaboradores</h4>

    <form method="post" onsubmit="return confirmarEnvio();">
        <table class="table table-bordered table-success">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Código</th>
                    <th>Maquiagem</th>
                    <th>Skin Care</th>
                    <th>Eudora</th>
                    <th>Total do Mês</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($colaboradores as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['NomeCompleto']) ?></td>
                    <td><?= htmlspecialchars($c['CodigoExterno']) ?></td>
                    <td>
                        <input type="text" name="colaboradores[<?= $c['ColaboradorID'] ?>][meta_maquiagem]" 
                               class="form-control moeda" 
                               value="<?= number_format($c['MetaMaquiagem'], 2, ',', '.') ?>" required>
                    </td>
                    <td>
                        <input type="text" name="colaboradores[<?= $c['ColaboradorID'] ?>][meta_skincare]" 
                               class="form-control moeda" 
                               value="<?= number_format($c['MetaSkinCare'], 2, ',', '.') ?>" required>
                    </td>
                    <td>
                        <input type="text" name="colaboradores[<?= $c['ColaboradorID'] ?>][meta_produtos_gerais]" 
                               class="form-control moeda" 
                               value="<?= number_format($c['MetaProdutosGerais'], 2, ',', '.') ?>" required>
                    </td>
                    <td>
                        <input type="text" class="form-control total" readonly>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="dashboard.php" class="btn btn-secondary px-4">Voltar ao Início</a>
            <button type="submit" class="btn btn-primary px-4">Salvar Todas</button>
        </div>
    </form>
</div>

<script>
function formatarMoeda(valor) {
    valor = valor.replace(/\D/g, '');
    if (valor.length === 0) return '';
    valor = (parseInt(valor) / 100).toFixed(2) + "";
    valor = valor.replace(".", ",");
    valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    return valor;
}

function moedaParaFloat(valor) {
    return parseFloat(valor.replace(/\./g, '').replace(',', '.')) || 0;
}

function atualizarTotal(tr) {
    const campos = tr.querySelectorAll(".moeda");
    const total = tr.querySelector(".total");
    let soma = 0;
    campos.forEach(input => soma += moedaParaFloat(input.value));
    total.value = soma.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.querySelectorAll(".moeda").forEach(input => {
    input.value = formatarMoeda(input.value.replace(/\D/g, ''));

    input.addEventListener("input", e => {
        let valor = e.target.value.replace(/\D/g, "");
        e.target.value = formatarMoeda(valor);
        atualizarTotal(e.target.closest("tr"));
    });

    atualizarTotal(input.closest("tr"));
});

function confirmarEnvio() {
    return confirm("Tem certeza que deseja salvar todas as metas?");
}
</script>
</body>
</html>
