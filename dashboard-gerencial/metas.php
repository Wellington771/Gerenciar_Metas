<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuração do banco de dados
$host = 'localhost';
$usuario_db = 'root';
$senha_db = '';
$banco = 'gerenciadormetasdb';

$conn = new mysqli($host, $usuario_db, $senha_db, $banco);
if ($conn->connect_error) {
    die('Erro de conexão: ' . $conn->connect_error);
}

// Função para converter moeda brasileira para float
function moedaParaFloatPHP($valor) {
    $valor = str_replace('.', '', $valor);
    $valor = str_replace(',', '.', $valor);
    return floatval($valor);
}

// Defina o mês de referência (exemplo: mês atual)
$mesReferencia = date('Y-m-01');

// Busca todos os colaboradores que NÃO são Admin
$colaboradores = [];
$result = $conn->query("SELECT ColaboradorID, CodigoExterno, Email FROM colaboradores WHERE Ativo = 1 AND NivelAcesso = 'Colaborador' ORDER BY Email");
while ($row = $result->fetch_assoc()) {
    $colaboradores[$row['ColaboradorID']] = $row;
}

// Busca todos os produtos
$produtos = [];
$result = $conn->query("SELECT ProdutoID, NomeProduto FROM produtos ORDER BY NomeProduto");
while ($row = $result->fetch_assoc()) {
    $produtos[$row['ProdutoID']] = $row['NomeProduto'];
}

// Busca metas já cadastradas para o mês de referência
$metas = [];
$sql = "SELECT * FROM metas WHERE MesReferencia = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $mesReferencia);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $metas[$row['ColaboradorID']][$row['ProdutoID']] = $row;
}
$stmt->close();

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['metas'])) {
    foreach ($_POST['metas'] as $colaboradorId => $produtosMeta) {
        foreach ($produtosMeta as $produtoId => $valorMeta) {
            $valorMeta = moedaParaFloatPHP($valorMeta);

            // Verifica se já existe meta para esse colaborador/produto/mês
            $sql = "SELECT MetaID FROM metas WHERE ColaboradorID = ? AND ProdutoID = ? AND MesReferencia = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iis', $colaboradorId, $produtoId, $mesReferencia);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->close();
                $sql = "UPDATE metas SET ValorMeta = ? WHERE ColaboradorID = ? AND ProdutoID = ? AND MesReferencia = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('diis', $valorMeta, $colaboradorId, $produtoId, $mesReferencia);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt->close();
                $sql = "INSERT INTO metas (ColaboradorID, ProdutoID, MesReferencia, ValorMeta) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iisd', $colaboradorId, $produtoId, $mesReferencia, $valorMeta);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    header("Location: dashboard.php");
    exit;
}
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
    <h4 class="mb-4" style="color:#2e7d32;">Definir Metas dos Colaboradores (<?= date('m/Y', strtotime($mesReferencia)) ?>)</h4>
    <form method="post" onsubmit="return confirmarEnvio();">
        <table class="table table-bordered table-success">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <?php foreach ($produtos as $produtoNome): ?>
                        <th><?= htmlspecialchars($produtoNome) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($colaboradores as $colaboradorId => $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['Email']) ?> <br><small><?= htmlspecialchars($c['CodigoExterno']) ?></small></td>
                    <?php foreach ($produtos as $produtoId => $produtoNome): ?>
                        <td>
                            <input type="text" 
                                   name="metas[<?= $colaboradorId ?>][<?= $produtoId ?>]" 
                                   class="form-control moeda"
                                   value="<?= isset($metas[$colaboradorId][$produtoId]) ? number_format($metas[$colaboradorId][$produtoId]['ValorMeta'], 2, ',', '.') : '' ?>">
                        </td>
                    <?php endforeach; ?>
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
// Máscara de moeda
document.querySelectorAll(".moeda").forEach(input => {
    input.addEventListener("input", function(e) {
        let v = e.target.value.replace(/\D/g, "");
        v = (parseInt(v) / 100).toFixed(2) + "";
        v = v.replace(".", ",");
        v = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        e.target.value = v;
    });
});

// Confirma envio do formulário
function confirmarEnvio() {
    return confirm("Tem certeza que deseja salvar todas as metas?");
}
</script>
</body>
</html>