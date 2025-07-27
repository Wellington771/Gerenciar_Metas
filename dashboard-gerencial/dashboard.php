<?php
// dashboard.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/database.php';
$msg = '';
// Processamento do CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $csvPath = 'uploads/' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $csvPath);
        $handle = fopen($csvPath, 'r');
        if ($handle) {
            // Ignora cabeçalho
            $header = fgetcsv($handle, 1000, ';');
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                // Ajuste os índices conforme o CSV
                $codigo = $data[0]; // CodigoRevendedor
                $valor = floatval(str_replace(',', '.', $data[1])); // TotalPraticado
                // Busca ColaboradorID
                $stmt = $pdo->prepare('SELECT ColaboradorID FROM Colaboradores WHERE CodigoExterno = ?');
                $stmt->execute([$codigo]);
                $colab = $stmt->fetch();
                if ($colab) {
                    $colabID = $colab['ColaboradorID'];
                    // Insere no histórico
                    $stmt = $pdo->prepare('INSERT INTO HistoricoVendas (ColaboradorID, ValorVenda, InfoCSV) VALUES (?, ?, ?)');
                    $stmt->execute([$colabID, $valor, implode(';', $data)]);
                    // Atualiza vendas
                    $stmt = $pdo->prepare('UPDATE Colaboradores SET ValorAtualVendas = ValorAtualVendas + ? WHERE ColaboradorID = ?');
                    $stmt->execute([$valor, $colabID]);
                }
            }
            fclose($handle);
            $msg = 'CSV processado com sucesso!';
        } else {
            $msg = 'Erro ao abrir o arquivo CSV.';
        }
    } else {
        $msg = 'Erro no upload do arquivo.';
    }
}
// Consulta colaboradores e receita total
$stmt = $pdo->query('SELECT * FROM Colaboradores ORDER BY ValorAtualVendas DESC');
$colaboradores = $stmt->fetchAll();
$receitaTotal = 0;
foreach ($colaboradores as $c) {
    $receitaTotal += $c['ValorAtualVendas'];
}

// Consulta vendas por categoria para cada colaboradora
$vendasPorCategoria = [];
foreach ($colaboradores as $c) {
    $id = $c['ColaboradorID'];
    $vendasPorCategoria[$id] = [
        'maquiagem' => 0,
        'skin care' => 0,
        'produtos gerais' => 0
    ];
    $stmt = $pdo->prepare("SELECT Categoria, SUM(ValorVenda) as total FROM HistoricoVendas WHERE ColaboradorID = ? GROUP BY Categoria");
    $stmt->execute([$id]);
    while ($row = $stmt->fetch()) {
        $cat = strtolower($row['Categoria']);
        $vendasPorCategoria[$id][$cat] = $row['total'];
    }
}
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
    <?php if ($msg): ?>
        <div class="alert alert-info mt-2"> <?php echo $msg; ?> </div>
    <?php endif; ?>
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card card-kpi mb-4 shadow">
                <div class="card-body text-center">
                    <h5 class="card-title">Receita Total</h5>
                    <h2>R$ <?php echo number_format($receitaTotal, 2, ',', '.'); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-4 shadow" style="border-left: 5px solid #388e3c;">
                <div class="card-body">
                    <h5 class="card-title" style="color:#388e3c;">Resumo das Metas</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($colaboradores as $c): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong><?php echo htmlspecialchars($c['NomeCompleto']); ?></strong></span>
                            <span>Meta Mensal: <span class="badge bg-success">R$ <?php echo number_format($c['MetaMensal'], 2, ',', '.'); ?></span></span>
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
                    <th>Nome</th>
                    <th>Código</th>
                    <th>Vendas (R$)</th>
                    <th>Meta Mensal (R$)</th>
                    <th>Falta para Meta (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php $pos = 1; foreach ($colaboradores as $c): ?>
                <tr>
                    <td><?php echo $pos++; ?></td>
                    <td><?php echo htmlspecialchars($c['NomeCompleto']); ?></td>
                    <td><?php echo htmlspecialchars($c['CodigoExterno']); ?></td>
                    <td><?php echo number_format($c['ValorAtualVendas'], 2, ',', '.'); ?></td>
                    <td><?php echo number_format($c['MetaMensal'], 2, ',', '.'); ?></td>
                    <td>
                        <?php 
                        $falta = $c['MetaMensal'] - $c['ValorAtualVendas'];
                        echo $falta > 0 ? number_format($falta, 2, ',', '.') : 'Meta atingida!';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h4 style="color:#388e3c;" class="mt-5"><i class="bi bi-brush"></i> Dashboard Maquiagem</h4>
    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Meta Maquiagem (R$)</th>
                    <th>Vendas Maquiagem (R$)</th>
                    <th>Falta para Meta (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['NomeCompleto']); ?></td>
                    <td><?php echo isset($c['MetaMaquiagem']) ? number_format($c['MetaMaquiagem'], 2, ',', '.') : '0,00'; ?></td>
                    <td><?php echo isset($vendasPorCategoria[$c['ColaboradorID']]['maquiagem']) ? number_format($vendasPorCategoria[$c['ColaboradorID']]['maquiagem'], 2, ',', '.') : '0,00'; ?></td>
                    <td>
                        <?php 
                        $meta = isset($c['MetaMaquiagem']) ? $c['MetaMaquiagem'] : 0;
                        $venda = isset($vendasPorCategoria[$c['ColaboradorID']]['maquiagem']) ? $vendasPorCategoria[$c['ColaboradorID']]['maquiagem'] : 0;
                        $falta = $meta - $venda;
                        echo $falta > 0 ? number_format($falta, 2, ',', '.') : 'Meta atingida!';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h4 style="color:#388e3c;" class="mt-5"><i class="bi bi-droplet"></i> Dashboard Skin Care</h4>
    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Meta Skin Care (R$)</th>
                    <th>Vendas Skin Care (R$)</th>
                    <th>Falta para Meta (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['NomeCompleto']); ?></td>
                    <td><?php echo isset($c['MetaSkinCare']) ? number_format($c['MetaSkinCare'], 2, ',', '.') : '0,00'; ?></td>
                    <td><?php echo isset($vendasPorCategoria[$c['ColaboradorID']]['skin care']) ? number_format($vendasPorCategoria[$c['ColaboradorID']]['skin care'], 2, ',', '.') : '0,00'; ?></td>
                    <td>
                        <?php 
                        $meta = isset($c['MetaSkinCare']) ? $c['MetaSkinCare'] : 0;
                        $venda = isset($vendasPorCategoria[$c['ColaboradorID']]['skin care']) ? $vendasPorCategoria[$c['ColaboradorID']]['skin care'] : 0;
                        $falta = $meta - $venda;
                        echo $falta > 0 ? number_format($falta, 2, ',', '.') : 'Meta atingida!';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h4 style="color:#388e3c;" class="mt-5"><i class="bi bi-box"></i> Dashboard Produtos Gerais</h4>
    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Meta Produtos Gerais (R$)</th>
                    <th>Vendas Produtos Gerais (R$)</th>
                    <th>Falta para Meta (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['NomeCompleto']); ?></td>
                    <td><?php echo isset($c['MetaProdutosGerais']) ? number_format($c['MetaProdutosGerais'], 2, ',', '.') : '0,00'; ?></td>
                    <td><?php echo isset($vendasPorCategoria[$c['ColaboradorID']]['produtos gerais']) ? number_format($vendasPorCategoria[$c['ColaboradorID']]['produtos gerais'], 2, ',', '.') : '0,00'; ?></td>
                    <td>
                        <?php 
                        $meta = isset($c['MetaProdutosGerais']) ? $c['MetaProdutosGerais'] : 0;
                        $venda = isset($vendasPorCategoria[$c['ColaboradorID']]['produtos gerais']) ? $vendasPorCategoria[$c['ColaboradorID']]['produtos gerais'] : 0;
                        $falta = $meta - $venda;
                        echo $falta > 0 ? number_format($falta, 2, ',', '.') : 'Meta atingida!';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
</html>
