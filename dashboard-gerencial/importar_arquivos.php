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
$msg = '';

// --- FUNÇÃO AUXILIAR PARA CONVERTER MOEDA ---
function moedaParaFloatImport($valor) {
    // Remove "R$", espaços, e pontos de milhar, depois troca vírgula decimal por ponto
    $valor = str_replace(['R$', ' ', '.'], '', $valor);
    $valor = str_replace(',', '.', $valor);
    return (float) $valor;
}

// --- LÓGICA PARA IMPORTAÇÃO DE ARQUIVOS POR CATEGORIA (AGORA USANDO O NOVO FORMATO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_action'])) {
    $categoria_import = $_POST['categoria_import'] ?? '';
    // Se for um dos botões de maquiagem, força a categoria para 'Maquiagem'
    $categoriasMaquiagem = ['Maquiagem', 'maquiagem', 'Eudora - 23178', 'Boti - 93', 'QDB - 38503'];
    if (in_array($categoria_import, $categoriasMaquiagem)) {
        $categoria_import = 'Maquiagem';
    }
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
                $delimiter = ';'; // O novo formato de CSV usa ponto e vírgula como delimitador

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

                            $infoCSV = "CodigoRevendedor:$codigoRevendedor|Linha:$i|" . implode('|', $row);
                            $stmtInsert = $pdo->prepare('INSERT INTO HistoricoVendas (ColaboradorID, CodigoRevendedor, ValorVenda, Categoria, DataVenda, InfoCSV) VALUES (?, ?, ?, ?, ?, ?)');
                            $stmtInsert->execute([$colaboradorID, $codigoRevendedor, $valorVenda, $categoria_import, $dataVendaStr, $infoCSV]);

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

// Lógica de extração de arquivos CSV removida
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
        padding-top: 20px;
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

    /* Estilos melhorados para seleção de colunas */
    .column-selection {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
    }

    .form-check {
        margin-bottom: 8px;
        padding: 5px 10px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .form-check:hover {
        background-color: #e9ecef;
    }

    .form-check-input:checked {
        background-color: #2e7d32;
        border-color: #2e7d32;
    }

    .selection-counter {
        position: sticky;
        top: 0;
        background: white;
        padding: 10px;
        border-bottom: 1px solid #dee2e6;
        margin: -15px -15px 15px -15px;
        border-radius: 8px 8px 0 0;
        font-weight: bold;
        color: #2e7d32;
    }

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

        .column-selection {
            max-height: 200px;
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
                <i class="bi bi-arrow-left"></i> Voltar ao Inicio
            </button>
        </div>
        <div class="card-body">
            
            <!-- Abas de navegação -->
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="importar-tab" data-bs-toggle="tab" data-bs-target="#importar" type="button" role="tab">
                        <i class="bi bi-upload"></i> Importar Dados
                    </button>
                </li>
                <!-- Aba Extrair removida -->
            </ul>

            <div class="tab-content" id="myTabContent">
                <!-- Apenas Aba Importar -->
                <div class="tab-pane fade show active" id="importar" role="tabpanel">
                    
                    <!-- Seção Maquiagem -->
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

                    <!-- Seção Skin Care -->
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

                    <!-- Seção Eudora -->
                    <h3><i class="bi bi-box"></i> Eudora</h3>

                    <div class="item">
                        <span>Produtos Gerais</span>
                        <label class="btn">
                            <i class="bi bi-upload"></i> Importar
                            <input type="file" onchange="handleFile(this, 'Produtos Gerais')">
                        </label>
                    </div>

                </div>

                <!-- Aba Extrair removida -->
            </div>

            <!-- Botão para voltar ao início -->
            <button type="button" class="btn-voltar" onclick="console.log('Redirecionando para dashboard.php (fundo)'); window.location.href='dashboard.php'">
                <i class="bi bi-house"></i> Voltar ao Inicio
            </button>

        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- FUNÇÕES PARA A ABA DE IMPORTAÇÃO ---
function handleFile(input, category = '') {
    const file = input.files[0];
    if (file) {
        let confirmMsg = `Deseja importar o arquivo "${file.name}" para a categoria "${category}"?`;

        if (confirm(confirmMsg)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.enctype = 'multipart/form-data';
            form.style.display = 'none';

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.name = 'import_file';
            fileInput.files = input.files;
            form.appendChild(fileInput);

            const categoryInput = document.createElement('input');
            categoryInput.type = 'hidden';
            categoryInput.name = 'categoria_import';
            categoryInput.value = category;
            form.appendChild(categoryInput);
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'import_action';
            actionInput.value = '1';
            form.appendChild(actionInput);

            document.body.appendChild(form);
            form.submit();
        }
    }
}

// Funções da aba de extração removidas
</script>
</body>
</html>
