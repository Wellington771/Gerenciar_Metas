<?php
// dashboard.php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php'); // Redireciona para login se não estiver logado
    exit;
}

// Conecta ao banco de dados
require_once 'config/database.php';

$msg = ''; // Mensagem de status do processamento do arquivo

// =========================================================================
// LÓGICA DE UPLOAD E PROCESSAMENTO DE ARQUIVO
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // O código aqui espera um arquivo .xlsx.
    // Lembre-se: é necessário uma biblioteca como o PhpSpreadsheet para ler .xlsx.
    // O código abaixo é um exemplo de como seria a lógica de importação
    // assumindo que a biblioteca já está instalada e configurada.
    
    if ($ext !== 'xlsx') {
        $msg = 'Erro: Por favor, envie um arquivo Excel (.xlsx) válido.';
    } else if ($file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads';

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
                // *** ATENÇÃO: A LÓGICA DE LEITURA DO EXCEL ESTÁ FALTANDO ***
                // Você precisa de uma biblioteca (como o PhpSpreadsheet) para ler
                // o arquivo Excel. O trecho abaixo é APENAS um esboço.
                
                // Exemplo de como usar o PhpSpreadsheet (precisa estar instalado via Composer)
                // require 'vendor/autoload.php';
                // use PhpOffice\PhpSpreadsheet\IOFactory;
                // $spreadsheet = IOFactory::load($excelPath);
                // $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                
                // Aqui o código original tentava usar a variável $header e $rows, que
                // não existem. A lógica de processamento foi removida e precisa
                // ser adicionada aqui após a leitura do arquivo.
                
                $msg = 'Importação de Excel em desenvolvimento. Arquivo ' . basename($file['name']) . ' enviado com sucesso.';
            }
        }
    } else {
        $msg = 'Erro no upload do arquivo.';
    }
}

// =========================================================================
// LÓGICA DE BUSCA DE DADOS PARA A DASHBOARD (UNIFICADA)
// =========================================================================

// Consulta geral de colaboradores para exibição na dashboard
// A coluna 'ValorAtualVendas' deve existir na tabela 'Colaboradores'
$stmt = $pdo->query('SELECT * FROM Colaboradores ORDER BY ValorAtualVendas DESC');
$colaboradores = $stmt->fetchAll();

// Calcula a receita total
$receitaTotal = 0;
foreach ($colaboradores as $c) {
    $receitaTotal += $c['ValorAtualVendas'];
}

// Calcula vendas por categoria para cada colaborador
$vendasPorCategoria = [];
foreach ($colaboradores as $c) {
    $id = $c['ColaboradorID'];
    $vendasPorCategoria[$id] = [
        'maquiagem' => 0,
        'skin care' => 0,
        'produtos gerais' => 0
    ];

    // A coluna 'Categoria' deve existir na tabela 'HistoricoVendas'
    $stmt = $pdo->prepare("SELECT Categoria, SUM(ValorVenda) as total 
                            FROM HistoricoVendas 
                            WHERE ColaboradorID = ? 
                            GROUP BY Categoria");
    $stmt->execute([$id]);

    while ($row = $stmt->fetch()) {
        $cat = strtolower($row['Categoria']);
        // Garante que a chave existe antes de atribuir
        if (isset($vendasPorCategoria[$id][$cat])) {
            $vendasPorCategoria[$id][$cat] = $row['total'];
        }
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
        body { background: #e8f5e9; } /* Fundo verde-claro */
        .card-kpi { background: #388e3c; color: #fff; } /* Cartão de receita total */
        .table-success { background: #c8e6c9; }
        .btn-success { background-color: #388e3c; border: none; }
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
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container">

    <?php if ($msg): ?>
        <div class="alert alert-info mt-2 text-center" style="font-size:1.2rem;max-width:600px;margin:20px auto;">
            <?php echo $msg; ?>
        </div>
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
                            <span>Meta Mensal: 
                                <span class="badge bg-success">R$ <?php echo number_format($c['MetaMensal'], 2, ',', '.'); ?></span>
                            </span>
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
                    <td><?php echo number_format($c['MetaMaquiagem'], 2, ',', '.'); ?></td>
                    <td><?php echo number_format($vendasPorCategoria[$c['ColaboradorID']]['maquiagem'], 2, ',', '.'); ?></td>
                    <td>
                        <?php 
                        $meta = $c['MetaMaquiagem'];
                        $venda = $vendasPorCategoria[$c['ColaboradorID']]['maquiagem'];
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
                    <td><?php echo number_format($c['MetaSkinCare'], 2, ',', '.'); ?></td>
                    <td><?php echo number_format($vendasPorCategoria[$c['ColaboradorID']]['skin care'], 2, ',', '.'); ?></td>
                    <td>
                        <?php 
                        $meta = $c['MetaSkinCare'];
                        $venda = $vendasPorCategoria[$c['ColaboradorID']]['skin care'];
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
                    <td><?php echo number_format($c['MetaProdutosGerais'], 2, ',', '.'); ?></td>
                    <td><?php echo number_format($vendasPorCategoria[$c['ColaboradorID']]['produtos gerais'], 2, ',', '.'); ?></td>
                    <td>
                        <?php 
                        $meta = $c['MetaProdutosGerais'];
                        $venda = $vendasPorCategoria[$c['ColaboradorID']]['produtos gerais'];
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>