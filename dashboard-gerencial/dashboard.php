<?php
// dashboard.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/database.php';
//require_once __DIR__ . '/../vendor/autoload.php'; // Adicione esta linha para usar PhpSpreadsheet
$msg = '';
// Processamento do CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'xlsx') {
        $msg = 'Erro: Por favor, envie um arquivo Excel (.xlsx) válido.';
    } else if ($file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads';
        // Garante que a pasta existe
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $msg = 'Erro: Não foi possível criar a pasta uploads. Verifique permissões.';
            }
        }
        if (empty($msg)) {
            $excelPath = $uploadDir . '/' . uniqid('excel_', true) . '_' . basename($file['name']);
            if (!move_uploaded_file($file['tmp_name'], $excelPath)) {
                $msg = 'Erro ao mover o arquivo enviado. Verifique permissões da pasta uploads.';
            } else {
                // Leitura do Excel usando PhpSpreadsheet
               // $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelPath);
                //$sheet = $spreadsheet->getActiveSheet();
               // $rows = $sheet->toArray();
               // $header = $rows[0];
               // $headerEsperado = [
               //     'Código Vendedor',
               //     'Vendedor',
               //     'Total Praticado',
               //     'Código Revendedor',
               //     'Revendedor'
               // ];
                // Normaliza header lido (remove espaços, aspas, caracteres não imprimíveis)
                $header = array_map(function($h) { 
                    return trim(str_replace(['"', "'", "\u{00a0}"], '', $h)); 
                }, $header);
                $headerOk = $header === $headerEsperado;
                if (!$headerOk) {
                    $msg = 'Erro: O arquivo Excel não está no formato esperado. Verifique o cabeçalho e a ordem das colunas.';
                    // Não atualiza dashboard se erro de cabeçalho
                    return;
                }
                $linhasProcessadas = 0;
                $linhasInvalidas = 0;
                foreach (array_slice($rows, 1) as $data) {
                    // Pula linhas vazias ou incompletas
                    if (count($data) < 5) {
                        $linhasInvalidas++;
                        continue;
                    }
                    $codigoRevendedor = trim($data[3]);
                    $valorStr = trim($data[2]);
                    // Remove "R$", espaços e troca vírgula por ponto
                    $valorStr = str_replace(['R$', 'r$', ' '], '', $valorStr);
                    $valorStr = str_replace(',', '.', $valorStr);
                    if (!is_numeric($valorStr)) {
                        $linhasInvalidas++;
                        continue;
                    }
                    $valor = floatval($valorStr);
                    // Busca ColaboradorID
                    $stmt = $pdo->prepare('SELECT ColaboradorID FROM Colaboradores WHERE CodigoExterno = ?');
                    $stmt->execute([$codigoRevendedor]);
                    $colab = $stmt->fetch();
                    if ($colab) {
                        $colabID = $colab['ColaboradorID'];
                        // Tenta identificar categoria (ajuste conforme sua regra)
                        $categoria = 'produtos gerais'; // padrão para aparecer no dashboard correto
                        // Exemplo: se quiser categorizar por nome do produto, adicione lógica aqui
                        // if (stripos($data[1], 'maquiagem') !== false) $categoria = 'maquiagem';
                        // if (stripos($data[1], 'skin care') !== false) $categoria = 'skin care';
                        // Insere no histórico
                        $stmt = $pdo->prepare('INSERT INTO HistoricoVendas (ColaboradorID, ValorVenda, Categoria, InfoCSV) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$colabID, $valor, $categoria, implode(';', $data)]);
                        // Atualiza vendas
                        $stmt = $pdo->prepare('UPDATE Colaboradores SET ValorAtualVendas = ValorAtualVendas + ? WHERE ColaboradorID = ?');
                        $stmt->execute([$valor, $colabID]);
                        $linhasProcessadas++;
                    } else {
                        $linhasInvalidas++;
                        continue;
                    }
                }
                if ($linhasProcessadas > 0) {
                    $msg = 'CSV processado com sucesso! ' . $linhasProcessadas . ' linha(s) inserida(s).' . ($linhasInvalidas > 0 ? ' ' . $linhasInvalidas . ' linha(s) ignorada(s) por erro.' : '');
                    // Atualiza dashboard imediatamente (recarrega dados)
                    $stmt = $pdo->query('SELECT * FROM Colaboradores ORDER BY ValorAtualVendas DESC');
                    $colaboradores = $stmt->fetchAll();
                    $receitaTotal = 0;
                    foreach ($colaboradores as $c) {
                        $receitaTotal += $c['ValorAtualVendas'];
                    }
                    // Atualiza vendas por categoria para cada colaborador
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
                }
            }
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
<style>
    .top-action-btn {
        font-weight: bold !important;
        color: #1b3c1b !important;
        background: #e8f5e9 !important;
        border: 2px solid #388e3c !important;
        margin-left: 10px;
        transition: background 0.2s, color 0.2s;
    }
    .top-action-btn:hover, .top-action-btn:focus {
        background: #388e3c !important;
        color: #fff !important;
    }
</style>

<div class="container">
    <?php if ($msg): ?>
        <div class="alert alert-info mt-2 text-center" style="font-size:1.2rem;max-width:600px;margin:20px auto;"> <?php echo $msg; ?> </div>
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

    <h4 style="color:#388e3c;" class="mt-5"><i class="bi bi-brush"></i> Maquiagem</h4>
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

    <h4 style="color:#388e3c;" class="mt-5"><i class="bi bi-droplet"></i> Skin Care</h4>
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

    <h4 style="color:#388e3c;" class="mt-5"><i class="bi bi-box"></i> Eudora</h4>
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
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
