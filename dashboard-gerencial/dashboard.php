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

function calcularPenetracaoMaquiagem($pdo, $colaboradorId, $mesAno = null) {
    if (!$mesAno) {
        $mesAno = date('Y-m'); // Mês atual se não especificado
    }
    
    // Conta revendedores únicos que o colaborador atendeu no mês
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT CodigoRevendedor) as total_revendedores
        FROM HistoricoVendas 
        WHERE ColaboradorID = ? 
        AND CodigoRevendedor IS NOT NULL 
        AND CodigoRevendedor != ''
        AND DATE_FORMAT(DataVenda, '%Y-%m') = ?
    ");
    $stmt->execute([$colaboradorId, $mesAno]);
    $totalRevendedores = $stmt->fetchColumn();
    
    if ($totalRevendedores == 0) {
        return [
            'penetracao' => 0,
            'revendedores_maquiagem' => 0,
            'total_revendedores' => 0
        ];
    }
    
    // Conta revendedores únicos que compraram maquiagem no mês
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT CodigoRevendedor) as revendedores_maquiagem
        FROM HistoricoVendas 
        WHERE ColaboradorID = ? 
        AND CodigoRevendedor IS NOT NULL 
        AND CodigoRevendedor != ''
        AND LOWER(Categoria) LIKE '%maquiagem%'
        AND DATE_FORMAT(DataVenda, '%Y-%m') = ?
    ");
    $stmt->execute([$colaboradorId, $mesAno]);
    $revendedoresMaquiagem = $stmt->fetchColumn();
    
    // Calcula penetração: (revendedores que compraram maquiagem / total revendedores) * 100
    $penetracao = ($revendedoresMaquiagem / $totalRevendedores) * 100;
    
    return [
        'penetracao' => round($penetracao, 2),
        'revendedores_maquiagem' => $revendedoresMaquiagem,
        'total_revendedores' => $totalRevendedores
    ];
}

// Verifica se houve envio de arquivo via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // Obtém extensão do arquivo

    // Verifica se é um arquivo .xlsx válido
    if ($ext !== 'xlsx') {
        $msg = 'Erro: Por favor, envie um arquivo Excel (.xlsx) válido.';
    } else if ($file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads';

        // Garante que a pasta de upload existe
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $msg = 'Erro: Não foi possível criar a pasta uploads. Verifique permissões.';
            }
        }

        if (empty($msg)) {
            // Define nome único para o arquivo
            $excelPath = $uploadDir . '/' . uniqid('excel_', true) . '_' . basename($file['name']);

            // Move o arquivo para a pasta uploads
            if (!move_uploaded_file($file['tmp_name'], $excelPath)) {
                $msg = 'Erro ao mover o arquivo enviado. Verifique permissões da pasta uploads.';
            } else {

                // *** Aqui faltou a leitura do arquivo com biblioteca como PhpSpreadsheet ***

                // Normaliza o cabeçalho removendo espaços, aspas e caracteres invisíveis
                $header = array_map(function($h) { 
                    return trim(str_replace(['"', "'", "\u{00a0}"], '', $h)); 
                }, $header);

                // Verifica se o cabeçalho está no formato correto
                $headerOk = $header === $headerEsperado;
                if (!$headerOk) {
                    $msg = 'Erro: O arquivo Excel não está no formato esperado. Verifique o cabeçalho e a ordem das colunas.';
                    return;
                }

                // Inicializa contadores
                $linhasProcessadas = 0;
                $linhasInvalidas = 0;

                // Percorre os dados (ignorando a primeira linha que é cabeçalho)
                foreach (array_slice($rows, 1) as $data) {
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

                    // Busca colaborador pelo código
                    $stmt = $pdo->prepare('SELECT ColaboradorID FROM Colaboradores WHERE CodigoExterno = ?');
                    $stmt->execute([$codigoRevendedor]);
                    $colab = $stmt->fetch();

                    if ($colab) {
                        $colabID = $colab['ColaboradorID'];

                        // Categorização simples (ajustável)
                        $categoria = 'produtos gerais';

                        $stmt = $pdo->prepare('INSERT INTO HistoricoVendas (ColaboradorID, ValorVenda, Categoria, InfoCSV, CodigoRevendedor) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$colabID, $valor, $categoria, implode(';', $data), $codigoRevendedor]);

                        // Atualiza o valor atual de vendas do colaborador
                        $stmt = $pdo->prepare('UPDATE Colaboradores SET ValorAtualVendas = ValorAtualVendas + ? WHERE ColaboradorID = ?');
                        $stmt->execute([$valor, $colabID]);

                        $linhasProcessadas++;
                    } else {
                        $linhasInvalidas++;
                        continue;
                    }
                }

                // Mostra resultado final da importação
                if ($linhasProcessadas > 0) {
                    $msg = 'CSV processado com sucesso! ' . $linhasProcessadas . ' linha(s) inserida(s).' . 
                          ($linhasInvalidas > 0 ? ' ' . $linhasInvalidas . ' linha(s) ignorada(s) por erro.' : '');

                    // Atualiza os dados para exibição na dashboard
                    $stmt = $pdo->query('SELECT * FROM Colaboradores ORDER BY ValorAtualVendas DESC');
                    $colaboradores = $stmt->fetchAll();

                    $receitaTotal = 0;
                    foreach ($colaboradores as $c) {
                        $receitaTotal += $c['ValorAtualVendas'];
                    }

                    // Prepara os dados por categoria
                    $vendasPorCategoria = [];
                    foreach ($colaboradores as $c) {
                        $id = $c['ColaboradorID'];
                        $vendasPorCategoria[$id] = [
                            'maquiagem' => 0,
                            'skin care' => 0,
                            'produtos gerais' => 0
                        ];

                        $stmt = $pdo->prepare("SELECT Categoria, SUM(ValorVenda) as total 
                                               FROM HistoricoVendas 
                                               WHERE ColaboradorID = ? 
                                               GROUP BY Categoria");
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

// Consulta geral de colaboradores para exibição na dashboard
$stmt = $pdo->query('SELECT * FROM Colaboradores ORDER BY ValorAtualVendas DESC');
$colaboradores = $stmt->fetchAll();

// Calcula a receita total somando vendas de todos os colaboradores
$receitaTotal = 0;
foreach ($colaboradores as $c) {
    $receitaTotal += $c['ValorAtualVendas'];
}

// Calcula vendas por categoria para cada colaborador (para exibir nas seções específicas)
$vendasPorCategoria = [];
$penetracaoPorColaborador = [];
foreach ($colaboradores as $c) {
    $id = $c['ColaboradorID'];
    $vendasPorCategoria[$id] = [
        'maquiagem' => 0,
        'skin care' => 0,
        'produtos gerais' => 0
    ];

    $stmt = $pdo->prepare("SELECT Categoria, SUM(ValorVenda) as total 
                           FROM HistoricoVendas 
                           WHERE ColaboradorID = ? 
                           GROUP BY Categoria");
    $stmt->execute([$id]);

    while ($row = $stmt->fetch()) {
        $cat = strtolower($row['Categoria']);
        $vendasPorCategoria[$id][$cat] = $row['total'];
    }
    
    $penetracaoPorColaborador[$id] = calcularPenetracaoMaquiagem($pdo, $id);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Gerencial</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos personalizados -->
    <style>
        body { background: #e8f5e9; } /* Fundo verde-claro */
        .card-kpi { background: #388e3c; color: #fff; } /* Cartão de receita total */
        .table-success { background: #c8e6c9; }
        .btn-success { background-color: #388e3c; border: none; }
        /* Estilos para indicadores de penetração */
        .penetracao-alta { color: #28a745; font-weight: bold; }
        .penetracao-media { color: #ffc107; font-weight: bold; }
        .penetracao-baixa { color: #dc3545; font-weight: bold; }
        .card-penetracao { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
    </style>
</head>
<body>

<!-- Cabeçalho -->
<?php include 'includes/header.php'; ?>

<!-- Estilo adicional para os botões do topo -->
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

    <!-- Exibe mensagem de sucesso ou erro do upload, se houver -->
    <?php if ($msg): ?>
        <div class="alert alert-info mt-2 text-center" style="font-size:1.2rem;max-width:600px;margin:20px auto;">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- KPIs principais -->
    <div class="row mt-4">
        <!-- Receita total -->
        <div class="col-md-3">
            <div class="card card-kpi mb-4 shadow">
                <div class="card-body text-center">
                    <h5 class="card-title">Receita Total</h5>
                    <h2>R$ <?php echo number_format($receitaTotal, 2, ',', '.'); ?></h2>
                </div>
            </div>
        </div>

        <!-- Novo KPI: Penetração Média de Maquiagem -->
        <div class="col-md-3">
            <div class="card card-penetracao mb-4 shadow">
                <div class="card-body text-center">
                    <h5 class="card-title">Penetração Média</h5>
                    <?php 
                    $penetracaoMedia = 0;
                    $colaboradoresComDados = 0;
                    foreach ($penetracaoPorColaborador as $pen) {
                        if ($pen['total_revendedores'] > 0) {
                            $penetracaoMedia += $pen['penetracao'];
                            $colaboradoresComDados++;
                        }
                    }
                    $penetracaoMedia = $colaboradoresComDados > 0 ? $penetracaoMedia / $colaboradoresComDados : 0;
                    ?>
                    <h2><?php echo number_format($penetracaoMedia, 1); ?>%</h2>
                    <small>Maquiagem</small>
                </div>
            </div>
        </div>

        <!-- Lista de metas dos colaboradores -->
        <div class="col-md-6">
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

    <!-- Nova seção: Penetração de Maquiagem -->
    <h4 class="mt-5 mb-3" style="color:#667eea;"><i class="bi bi-graph-up"></i> Penetração de Maquiagem</h4>
    <!-- Instrução de como funciona removida -->
    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Nome</th>
                    <th>Total Revendedores</th>
                    <th>Revendedores Maquiagem</th>
                    <th>Penetração (%)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['NomeCompleto']); ?></strong></td>
                    <td class="text-center"><?php echo $penetracaoPorColaborador[$c['ColaboradorID']]['total_revendedores']; ?></td>
                    <td class="text-center"><?php echo $penetracaoPorColaborador[$c['ColaboradorID']]['revendedores_maquiagem']; ?></td>
                    <td class="text-center">
                        <?php 
                        $pen = $penetracaoPorColaborador[$c['ColaboradorID']]['penetracao'];
                        $classe = $pen >= 70 ? 'penetracao-alta' : ($pen >= 40 ? 'penetracao-media' : 'penetracao-baixa');
                        ?>
                        <span class="<?php echo $classe; ?>"><?php echo number_format($pen, 1); ?>%</span>
                    </td>
                    <td class="text-center">
                        <?php 
                        if ($pen >= 70) {
                            echo '<span class="badge bg-success">Excelente ≥70%</span>';
                        } elseif ($pen >= 40) {
                            echo '<span class="badge bg-warning">Bom ≥40%</span>';
                        } else {
                            echo '<span class="badge bg-danger">Precisa Melhorar &lt;40%</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Ranking geral de vendedores -->
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

    <!-- Tabela de metas por categoria: Maquiagem -->
    <h4 style="color:#388e3c;" class="mt-5"><i class="bi bi-brush"></i> Maquiagem</h4>
    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Meta Maquiagem (R$)</th>
                    <th>Vendas Maquiagem (R$)</th>
                    <th>Falta para Meta (R$)</th>
                    <!-- Adiciona coluna de penetração na tabela de maquiagem -->
                    <th>Penetração (%)</th>
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
                    <!-- Exibe penetração na tabela de maquiagem -->
                    <td>
                        <?php 
                        $pen = $penetracaoPorColaborador[$c['ColaboradorID']]['penetracao'];
                        $classe = $pen >= 70 ? 'penetracao-alta' : ($pen >= 40 ? 'penetracao-media' : 'penetracao-baixa');
                        ?>
                        <span class="<?php echo $classe; ?>"><?php echo number_format($pen, 1); ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tabela de metas por categoria: Skin Care -->
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

    <!-- Tabela de metas por categoria: Produtos Gerais (Eudora) -->
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

<!-- Rodapé -->
<?php include 'includes/footer.php'; ?>

<!-- Ícones do Bootstrap -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
