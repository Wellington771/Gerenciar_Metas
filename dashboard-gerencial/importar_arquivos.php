<?php
// importar_arquivos.php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';

$msg = '';
$csvData = null; // Para a aba de extração
$filteredData = null; // Para a aba de extração

// --- FUNÇÃO AUXILIAR PARA CONVERTER MOEDA ---
function moedaParaFloatImport($valor) {
    // Remove "R$", espaços, e pontos de milhar, depois troca vírgula decimal por ponto
    $valor = str_replace(['R$', ' ', '.'], '', $valor);
    $valor = str_replace(',', '.', $valor);
    return (float) $valor;
}

// --- LÓGICA PARA IMPORTAÇÃO DE ARQUIVOS POR CATEGORIA (AGORA USANDO O NOVO FORMATO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_action'])) {
    $categoria_import = $_POST['categoria_import'] ?? ''; // Categoria específica para a importação
    $file = $_FILES['import_file'] ?? null;

    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $msg = 'Erro: Por favor, envie um arquivo CSV válido para importação.';
        } else {
            $csvContent = file_get_contents($file['tmp_name']);
            $lines = explode("\n", $csvContent);
            $lines = array_filter($lines, function($line) { return trim($line) !== ''; });

            if (count($lines) > 0) {
                // O novo formato de CSV usa ponto e vírgula como delimitador
                $delimiter = ';';

                $headers = str_getcsv($lines[0], $delimiter);
                $headers = array_map('trim', $headers); // Limpa cabeçalhos

                // Mapeamento fixo para o arquivo RelatorioItensPorVendedor
                // 'Código Vendedor' está no índice 0
                // 'Total Praticado' está no índice 2
                $colMap = [
                    'CodigoRevendedor' => 0,
                    'Valor' => 2,
                    'DataVenda' => false, // Não existe no CSV, usará a data atual
                ];

                // Verifica se as colunas essenciais existem nos índices esperados
                if (!isset($headers[$colMap['CodigoRevendedor']]) || !isset($headers[$colMap['Valor']])) {
                    $msg = 'Erro: O arquivo CSV não contém as colunas essenciais ("Código Vendedor" e/ou "Total Praticado") nos índices esperados.';
                } else {
                    $linhasProcessadas = 0;
                    $linhasIgnoradas = 0;

                    for ($i = 1; $i < count($lines); $i++) {
                        $row = str_getcsv($lines[$i], $delimiter); // Usa o delimitador correto
                        $row = array_map('trim', $row); // Limpa células

                        // Garante que a linha tem colunas suficientes antes de tentar acessar
                        if (count($row) <= max($colMap['CodigoRevendedor'], $colMap['Valor'])) {
                            $linhasIgnoradas++;
                            continue;
                        }

                        $codigoRevendedor = $row[$colMap['CodigoRevendedor']] ?? '';
                        $valorVendaStr = $row[$colMap['Valor']] ?? '';
                        $dataVendaStr = ($colMap['DataVenda'] !== false && isset($row[$colMap['DataVenda']])) ? $row[$colMap['DataVenda']] : date('Y-m-d H:i:s'); // Usa data atual se não houver

                        // Limpa o código do revendedor (remove pontos)
                        $codigoRevendedor = str_replace('.', '', $codigoRevendedor);

                        // Converte valor para float usando a função atualizada
                        $valorVenda = moedaParaFloatImport($valorVendaStr);

                        // Validação básica
                        if (empty($codigoRevendedor) || $valorVenda <= 0) {
                            $linhasIgnoradas++;
                            continue;
                        }

                        // Busca ColaboradorID pelo CodigoExterno
                        $stmtColab = $pdo->prepare('SELECT ColaboradorID FROM Colaboradores WHERE CodigoExterno = ?');
                        $stmtColab->execute([$codigoRevendedor]);
                        $colab = $stmtColab->fetch(PDO::FETCH_ASSOC);

                        if ($colab) {
                            $colaboradorID = $colab['ColaboradorID'];

                            // Insere no HistoricoVendas com a categoria correta
                            $stmtInsert = $pdo->prepare('INSERT INTO HistoricoVendas (ColaboradorID, ValorVenda, Categoria, DataVenda) VALUES (?, ?, ?, ?)');
                            $stmtInsert->execute([$colaboradorID, $valorVenda, $categoria_import, $dataVendaStr]);

                            // Atualiza ValorAtualVendas do colaborador (total geral)
                            $stmtUpdate = $pdo->prepare('UPDATE Colaboradores SET ValorAtualVendas = ValorAtualVendas + ? WHERE ColaboradorID = ?');
                            $stmtUpdate->execute([$valorVenda, $colaboradorID]);

                            $linhasProcessadas++;
                        } else {
                            $linhasIgnoradas++;
                        }
                    }
                    $msg = "Importação de '$categoria_import' concluída! $linhasProcessadas linha(s) processada(s). " . ($linhasIgnoradas > 0 ? "$linhasIgnoradas linha(s) ignorada(s)." : "");
                    // Redireciona para o dashboard após a importação
                    header("Location: dashboard.php?msg=" . urlencode($msg));
                    exit;
                }
            } else {
                $msg = 'Erro: Arquivo CSV vazio ou inválido.';
            }
        }
    } else if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        $msg = 'Erro no upload do arquivo: ' . $file['error'];
    }
}


// --- LÓGICA PARA EXTRAÇÃO DE ARQUIVOS (ABA SEPARADA) ---
// Processa upload de arquivo CSV para extração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file_extract'])) { // Renomeado para evitar conflito
    $file = $_FILES['csv_file_extract'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($ext !== 'csv') {
        $msg = 'Erro: Por favor, envie um arquivo CSV válido para extração.';
    } else if ($file['error'] === UPLOAD_ERR_OK) {
        $csvContent = file_get_contents($file['tmp_name']);
        
        // Parse do CSV
        $lines = explode("\n", $csvContent);
        $lines = array_filter($lines, function($line) { return trim($line) !== ''; });
        
        if (count($lines) > 0) {
            // Processa cabeçalho
            $headers = str_getcsv($lines[0]); // Delimitador padrão (vírgula) para extração
            $headers = array_map(function($h) { return trim(str_replace('"', '', $h)); }, $headers);
            
            // Processa dados
            $rows = [];
            for ($i = 1; $i < count($lines); $i++) {
                $row = str_getcsv($lines[$i]); // Delimitador padrão (vírgula) para extração
                $row = array_map(function($cell) { return trim(str_replace('"', '', $cell)); }, $row);
                $rows[] = $row;
            }
            
            $csvData = ['headers' => $headers, 'rows' => $rows];
            $filteredData = $csvData;
            
            $msg = 'Arquivo CSV carregado com sucesso para extração! ' . count($rows) . ' linhas encontradas.';
        }
    }
}

// Processa filtros para extração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_action']) && isset($_SESSION['csv_data'])) {
    $csvData = $_SESSION['csv_data'];
    $searchTerm = $_POST['search_term'] ?? '';
    $filterColumn = $_POST['filter_column'] ?? '';
    $filterValue = $_POST['filter_value'] ?? '';

    $filteredRows = $csvData['rows'];

    // Aplica busca geral
    if (!empty($searchTerm)) {
        $filteredRows = array_filter($filteredRows, function($row) use ($searchTerm) {
            foreach ($row as $cell) {
                if (stripos($cell, $searchTerm) !== false) {
                    return true;
                }
            }
            return false;
        });
    }

    // Aplica filtro por coluna
    if (!empty($filterColumn) && !empty($filterValue)) {
        $columnIndex = array_search($filterColumn, $csvData['headers']);
        if ($columnIndex !== false) {
            $filteredRows = array_filter($filteredRows, function($row) use ($columnIndex, $filterValue) {
                return isset($row[$columnIndex]) && stripos($row[$columnIndex], $filterValue) !== false;
            });
        }
    }

    $filteredData = ['headers' => $csvData['headers'], 'rows' => array_values($filteredRows)];
}

// Processa exportação para extração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_action']) && isset($_SESSION['csv_data'])) {
    $csvData = $_SESSION['csv_data'];
    $filteredData = $_SESSION['filtered_data'] ?? $csvData;
    $selectedColumns = $_POST['selected_columns'] ?? [];

    if (!empty($selectedColumns)) {
        $selectedIndices = [];
        foreach ($selectedColumns as $col) {
            $index = array_search($col, $filteredData['headers']);
            if ($index !== false) {
                $selectedIndices[] = $index;
            }
        }
        
        // Gera CSV
        $csvContent = implode(',', $selectedColumns) . "\n";
        foreach ($filteredData['rows'] as $row) {
            $selectedRow = [];
            foreach ($selectedIndices as $index) {
                $value = $row[$index] ?? '';
                $selectedRow[] = strpos($value, ',') !== false ? '"' . $value . '"' : $value;
            }
            $csvContent .= implode(',', $selectedRow) . "\n";
        }
        
        // Download do arquivo
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="dados_extraidos_' . date('Y-m-d_H-i-s') . '.csv"');
        echo $csvContent;
        exit;
    }
}

// Limpa sessão se solicitado (para extração)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_session'])) {
    unset($_SESSION['csv_data']);
    unset($_SESSION['filtered_data']);
    echo 'OK';
    exit;
}

// Salva dados na sessão para manter entre requisições (para extração)
if ($csvData) {
    $_SESSION['csv_data'] = $csvData;
}
if ($filteredData) {
    $_SESSION['filtered_data'] = $filteredData;
}

// Recupera dados da sessão se existirem (para extração)
if (!$csvData && isset($_SESSION['csv_data'])) {
    $csvData = $_SESSION['csv_data'];
    $filteredData = $_SESSION['filtered_data'] ?? $csvData;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Importar e Extrair Arquivos - Dashboard Gerencial</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f0f9f3;
        margin: 0;
        padding: 0;
        padding-top: 20px; /* Ajustado para o header */
    }

    .main-content {
        margin-left: 0;
        transition: margin-left 0.3s ease;
    }

    .container {
        max-width: 1200px;
        margin: 10px auto;
        padding: 0 20px;
    }

    .card {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        border: 1px solid #e1e1e1;
        border-left: 5px solid #2e7d32;
        margin-bottom: 20px;
    }

    .card-header {
        background: #2e7d32;
        color: white;
        padding: 15px 20px;
        border-radius: 10px 10px 0 0;
        border-bottom: none;
    }

    .card-body {
        padding: 25px;
    }

    h2, h3 {
        color: #2e7d32;
        margin-bottom: 15px;
    }

    h3 {
        border-bottom: 2px solid #2e7d32;
        padding-bottom: 5px;
        margin-top: 25px;
    }

    .item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f1f8f4;
        padding: 12px 20px;
        margin-top: 12px;
        border-radius: 8px;
        transition: 0.2s;
    }

    .item:hover {
        background: #e8f5e9;
    }

    .item span {
        font-weight: 500;
        color: #333;
    }

    .btn {
        background: #2e7d32;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        text-decoration: none;
        display: inline-block;
    }

    .btn:hover {
        background: #1b5e20;
        color: white;
    }

    .btn-success {
        background-color: #2e7d32;
        border: none;
    }

    .btn-success:hover {
        background-color: #1b5e20;
    }

    .btn-voltar {
        background-color: #616161;
        display: block;
        margin: 20px auto 0;
        padding: 12px 25px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        font-size: 15px;
        text-align: center;
        text-decoration: none;
        border: none;
    }

    .btn-voltar:hover {
        background-color: #424242;
        color: white;
    }

    input[type="file"] {
        display: none;
    }

    .table-success {
        background: #c8e6c9;
    }

    .badge-success {
        background-color: #2e7d32;
    }

    .text-success {
        color: #2e7d32 !important;
    }

    .alert-info {
        background-color: #e8f5e9;
        border-color: #2e7d32;
        color: #1b5e20;
    }

    .nav-tabs .nav-link {
        color: #2e7d32;
        border-color: transparent;
    }

    .nav-tabs .nav-link.active {
        background-color: #2e7d32;
        color: white;
        border-color: #2e7d32;
    }

    .nav-tabs .nav-link:hover {
        border-color: #2e7d32;
        color: #1b5e20;
    }

    /* Ajuste para mobile */
    @media (max-width: 768px) {
        body {
            padding-top: 15px;
        }
        
        .container {
            margin: 5px auto;
            padding: 0 15px;
        }
        
        .card-header {
            padding: 12px 15px;
        }
        
        .card-body {
            padding: 20px 15px;
        }
    }
</style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="main-content">
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">
                <i class="bi bi-cloud-upload"></i> Importar e Extrair Arquivos
            </h2>
            <button type="button" class="btn btn-light btn-sm" onclick="console.log('Redirecionando para dashboard.php (topo)'); window.location.href='dashboard.php'">
                <i class="bi bi-arrow-left"></i> Voltar ao Dashboard
            </button>
        </div>
        <div class="card-body">
            
             Abas de navegação 
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="importar-tab" data-bs-toggle="tab" data-bs-target="#importar" type="button" role="tab">
                        <i class="bi bi-upload"></i> Importar Dados
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="extrair-tab" data-bs-toggle="tab" data-bs-target="#extrair" type="button" role="tab">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Extrair Dados CSV
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                 Aba Importar 
                <div class="tab-pane fade show active" id="importar" role="tabpanel">
                    
                     A seção "Importar Vendas Gerais (Relatório Itens por Vendedor)" foi removida 

                     Seção Maquiagem 
                    <h3><i class="bi bi-brush"></i> Maquiagem</h3>
                    
                    <div class="item">
                        <span>Eudora - 23178</span>
                        <label class="btn">
                            <i class="bi bi-upload"></i> Importar
                            <input type="file" onchange="handleFile(this, 'Maquiagem')">
                        </label>
                    </div>

                    <div class="item">
                        <span>Boti - 93</span>
                        <label class="btn">
                            <i class="bi bi-upload"></i> Importar
                            <input type="file" onchange="handleFile(this, 'Maquiagem')">
                        </label>
                    </div>

                    <div class="item">
                        <span>QDB - 38503</span>
                        <label class="btn">
                            <i class="bi bi-upload"></i> Importar
                            <input type="file" onchange="handleFile(this, 'Maquiagem')">
                        </label>
                    </div>

                     Seção Skin Care 
                    <h3><i class="bi bi-droplet"></i> Skin Care</h3>

                    <div class="item">
                        <span>Eudora - 23132</span>
                        <label class="btn">
                            <i class="bi bi-upload"></i> Importar
                            <input type="file" onchange="handleFile(this, 'Skin Care')">
                        </label>
                    </div>

                    <div class="item">
                        <span>Boti - 1556</span>
                        <label class="btn">
                            <i class="bi bi-upload"></i> Importar
                            <input type="file" onchange="handleFile(this, 'Skin Care')">
                        </label>
                    </div>

                    <div class="item">
                        <span>QDB - 38599</span>
                        <label class="btn">
                            <i class="bi bi-upload"></i> Importar
                            <input type="file" onchange="handleFile(this, 'Skin Care')">
                        </label>
                    </div>

                     Seção Eudora 
                    <h3><i class="bi bi-box"></i> Eudora</h3>

                    <div class="item">
                        <span>Produtos Gerais</span>
                        <label class="btn">
                            <i class="bi bi-upload"></i> Importar
                            <input type="file" onchange="handleFile(this, 'Produtos Gerais')">
                        </label>
                    </div>

                </div>

                 Aba Extrair 
                <div class="tab-pane fade" id="extrair" role="tabpanel">
                    
                    <?php if ($msg): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                     Upload de arquivo 
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="csv_file_extract" class="form-label">
                                        <i class="bi bi-upload"></i> Selecionar arquivo CSV para extração
                                    </label>
                                    <input type="file" class="form-control" id="csv_file_extract" name="csv_file_extract" accept=".csv" required>
                                    <div class="form-text">
                                        Formatos aceitos: .csv (máximo 10MB)
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-cloud-upload"></i> Carregar Arquivo
                                </button>
                                <?php if ($csvData): ?>
                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="limparDados()">
                                    <i class="bi bi-x-circle"></i> Limpar Dados
                                </button>
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <?php if ($csvData): ?>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-success">
                                        <i class="bi bi-info-circle"></i> Informações do Arquivo
                                    </h6>
                                    <p class="mb-1">
                                        <span class="badge bg-success"><?php echo count($filteredData['rows']); ?></span> linhas
                                    </p>
                                    <p class="mb-0">
                                        <span class="badge bg-success"><?php echo count($csvData['headers']); ?></span> colunas
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($csvData): ?>
                     Filtros 
                    <div class="card mb-4">
                        <div class="card-header bg-light text-dark">
                            <h5 class="mb-0 text-success">
                                <i class="bi bi-funnel"></i> Filtros de Dados
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="filter_action" value="1">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="search_term" class="form-label">Busca Geral</label>
                                        <input type="text" class="form-control" id="search_term" name="search_term" 
                                               placeholder="Buscar em todas as colunas..." 
                                               value="<?php echo htmlspecialchars($_POST['search_term'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="filter_column" class="form-label">Filtrar por Coluna</label>
                                        <select class="form-select" id="filter_column" name="filter_column">
                                            <option value="">Selecionar coluna</option>
                                            <?php foreach ($csvData['headers'] as $header): ?>
                                                <option value="<?php echo htmlspecialchars($header); ?>" 
                                                        <?php echo ($_POST['filter_column'] ?? '') === $header ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($header); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="filter_value" class="form-label">Valor do Filtro</label>
                                        <input type="text" class="form-control" id="filter_value" name="filter_value" 
                                               placeholder="Valor para filtrar..." 
                                               value="<?php echo htmlspecialchars($_POST['filter_value'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-search"></i> Filtrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                     Seleção de colunas para exportação 
                    <div class="card mb-4">
                        <div class="card-header bg-light text-dark">
                            <h5 class="mb-0 text-success">
                                <i class="bi bi-check2-square"></i> Colunas para Exportar
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="export_action" value="1">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <button type="button" class="btn btn-outline-success btn-sm me-2" onclick="selecionarTodas()">
                                            Selecionar Todas
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselecionarTodas()">
                                            Desselecionar Todas
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <?php foreach ($csvData['headers'] as $header): ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input column-checkbox" type="checkbox" 
                                                   name="selected_columns[]" value="<?php echo htmlspecialchars($header); ?>" 
                                                   id="col_<?php echo md5($header); ?>" checked>
                                            <label class="form-check-label" for="col_<?php echo md5($header); ?>">
                                                <?php echo htmlspecialchars($header); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-download"></i> Exportar Dados Selecionados
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                     Visualização dos dados 
                    <div class="card">
                        <div class="card-header bg-light text-dark">
                            <h5 class="mb-0 text-success">
                                <i class="bi bi-table"></i> Visualização dos Dados
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-success">
                                        <tr>
                                            <?php foreach ($filteredData['headers'] as $header): ?>
                                                <th><?php echo htmlspecialchars($header); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $displayRows = array_slice($filteredData['rows'], 0, 50);
                                        foreach ($displayRows as $row): 
                                        ?>
                                        <tr>
                                            <?php foreach ($filteredData['headers'] as $index => $header): ?>
                                                <td><?php echo htmlspecialchars($row[$index] ?? ''); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (count($filteredData['rows']) > 50): ?>
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i> 
                                Mostrando apenas as primeiras 50 linhas. 
                                Total: <?php echo count($filteredData['rows']); ?> linhas. 
                                Use a exportação para obter todos os dados.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

             Botão para voltar ao início 
            <button type="button" class="btn-voltar" onclick="console.log('Redirecionando para dashboard.php (fundo)'); window.location.href='dashboard.php'">
                <i class="bi bi-house"></i> Voltar ao Dashboard
            </button>

        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- FUNÇÕES PARA A ABA DE IMPORTAÇÃO ---
function handleFile(input, category = '') { // Removido importType, agora é sempre 'category'
    const file = input.files[0];
    if (file) {
        let confirmMsg = `Deseja importar o arquivo "${file.name}" para a categoria "${category}"?`;

        if (confirm(confirmMsg)) {
            // Cria um formulário dinâmico para enviar o arquivo e os parâmetros
            const form = document.createElement('form');
            form.method = 'POST';
            form.enctype = 'multipart/form-data';
            form.style.display = 'none'; // Esconde o formulário

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.name = 'import_file';
            fileInput.files = input.files; // Atribui o arquivo selecionado
            form.appendChild(fileInput);

            const categoryInput = document.createElement('input');
            categoryInput.type = 'hidden';
            categoryInput.name = 'categoria_import';
            categoryInput.value = category;
            form.appendChild(categoryInput);
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'import_action'; // Nome genérico para a ação de importação
            actionInput.value = '1';
            form.appendChild(actionInput);

            document.body.appendChild(form);
            form.submit(); // Envia o formulário
        }
    }
}

// --- FUNÇÕES PARA A ABA DE EXTRAÇÃO CSV ---
function selecionarTodas() {
    const checkboxes = document.querySelectorAll('.column-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function deselecionarTodas() {
    const checkboxes = document.querySelectorAll('.column-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
}

function limparDados() {
    if (confirm('Tem certeza que deseja limpar todos os dados carregados para extração?')) {
        fetch('importar_arquivos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'clear_session=1'
        }).then(() => {
            location.reload();
        });
    }
}

// Mantém a aba ativa após reload
document.addEventListener('DOMContentLoaded', function() {
    const activeTab = localStorage.getItem('activeTab');
    if (activeTab) {
        const tabElement = document.querySelector(`#${activeTab}-tab`);
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    }
});

// Salva a aba ativa
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function(e) {
        const tabId = e.target.id.replace('-tab', '');
        localStorage.setItem('activeTab', tabId);
    });
});
</script>
</body>
</html>
