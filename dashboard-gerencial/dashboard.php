<?php
// dashboard.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

// Mês de referência (atual)
$mesReferencia = date('Y-m-01');

// Busca colaboradores ativos que NÃO são Admin
$stmt = $pdo->query("SELECT ColaboradorID, NomeCompleto, CodigoExterno FROM colaboradores WHERE Ativo = 1 AND NivelAcesso = 'Colaborador' ORDER BY NomeCompleto");
$colaboradores = $stmt->fetchAll();

// Busca metas do mês
$metas = [];
$stmt = $pdo->prepare("SELECT ColaboradorID, ProdutoID, ValorMeta FROM metas WHERE MesReferencia = ?");
$stmt->execute([$mesReferencia]);
while ($row = $stmt->fetch()) {
    $metas[$row['ColaboradorID']][$row['ProdutoID']] = $row['ValorMeta'];
}

// Busca vendas do mês
$vendas = [];
$stmt = $pdo->prepare("SELECT ColaboradorID, ProdutoID, SUM(ValorVenda) as TotalVendido FROM historicovendas WHERE DataVenda >= ? GROUP BY ColaboradorID, ProdutoID");
$stmt->execute([$mesReferencia]);
while ($row = $stmt->fetch()) {
    $vendas[$row['ColaboradorID']][$row['ProdutoID']] = $row['TotalVendido'];
}

// Busca produtos
$stmt = $pdo->query("SELECT ProdutoID, NomeProduto FROM produtos ORDER BY NomeProduto");
$produtos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Calcula totais por colaborador
$ranking = [];
foreach ($colaboradores as $colab) {
    $colabId = $colab['ColaboradorID'];
    $totalMeta = 0;
    $totalVendido = 0;
    foreach ($produtos as $prodId => $nomeProd) {
        $totalMeta += isset($metas[$colabId][$prodId]) ? $metas[$colabId][$prodId] : 0;
        $totalVendido += isset($vendas[$colabId][$prodId]) ? $vendas[$colabId][$prodId] : 0;
    }
    $ranking[] = [
        'colaborador' => $colab,
        'meta' => $totalMeta,
        'vendido' => $totalVendido,
        'falta' => max($totalMeta - $totalVendido, 0)
    ];
}

// Ordena ranking por vendido (desc)
usort($ranking, function($a, $b) {
    return $b['vendido'] <=> $a['vendido'];
});

// Receita total
$receitaTotal = array_sum(array_column($ranking, 'vendido'));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Gerencial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e8f5e9; }
        .card-kpi { background: #388e3c; color: #fff; }
        .table-success { background: #c8e6c9; }
        .btn-success { background-color: #388e3c; border: none; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card card-kpi mb-4 shadow">
                <div class="card-body text-center">
                    <h5 class="card-title">Receita Total do Mês</h5>
                    <h2>R$ <?= number_format($receitaTotal, 2, ',', '.') ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-4 shadow" style="border-left: 5px solid #388e3c;">
                <div class="card-body">
                    <h5 class="card-title" style="color:#388e3c;">Resumo das Metas do Mês</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($ranking as $r): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong><?= htmlspecialchars($r['colaborador']['NomeCompleto']) ?></strong> (<?= htmlspecialchars($r['colaborador']['CodigoExterno']) ?>)</span>
                            <span>Meta: <span class="badge bg-success">R$ <?= number_format($r['meta'], 2, ',', '.') ?></span></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mt-5 mb-3" style="color:#388e3c;"><i class="bi bi-trophy"></i> Ranking de Vendedores</h4>
    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Posição</th>
                    <th>Colaborador</th>
                    <th>Código</th>
                    <th>Vendas (R$)</th>
                    <th>Meta (R$)</th>
                    <th>Falta para Meta (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php $pos = 1; foreach ($ranking as $r): ?>
                <tr>
                    <td><?= $pos++ ?></td>
                    <td><?= htmlspecialchars($r['colaborador']['NomeCompleto']) ?></td>
                    <td><?= htmlspecialchars($r['colaborador']['CodigoExterno']) ?></td>
                    <td><?= number_format($r['vendido'], 2, ',', '.') ?></td>
                    <td><?= number_format($r['meta'], 2, ',', '.') ?></td>
                    <td>
                        <?= $r['falta'] > 0 ? number_format($r['falta'], 2, ',', '.') : 'Meta atingida!' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
