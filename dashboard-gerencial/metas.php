<?php
// Inicia a sessão do usuário
session_start();

// Importa a conexão com o banco de dados
require_once 'config/database.php';

// Função que converte um valor em formato de moeda brasileira para float
function moedaParaFloatPHP($valor) {
    $valor = str_replace('.', '', $valor); // remove pontos
    $valor = str_replace(',', '.', $valor); // troca vírgula por ponto
    return floatval($valor); // retorna como float
}

// Se o formulário foi enviado com dados dos colaboradores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['colaboradores'])) {
    // Percorre todos os colaboradores enviados
    foreach ($_POST['colaboradores'] as $id => $dados) {
        // Converte os valores das metas
        $maquiagem = moedaParaFloatPHP($dados['meta_maquiagem']);
        $skincare = moedaParaFloatPHP($dados['meta_skincare']);
        $produtosGerais = moedaParaFloatPHP($dados['meta_produtos_gerais']);

        // Soma total das metas do mês
        $metaTotal = $maquiagem + $skincare + $produtosGerais;

        // Atualiza no banco de dados
        $stmt = $pdo->prepare("UPDATE Colaboradores 
            SET MetaMaquiagem = ?, MetaSkinCare = ?, MetaProdutosGerais = ?, MetaMensal = ? 
            WHERE ColaboradorID = ?");
        $stmt->execute([$maquiagem, $skincare, $produtosGerais, $metaTotal, $id]);
    }

    // Redireciona de volta ao dashboard
    header("Location: dashboard.php");
    exit;
}

// Consulta todos os colaboradores
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

    <!-- Formulário de envio das metas -->
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

        <!-- Botões de ação -->
        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="dashboard.php" class="btn btn-secondary px-4">Voltar ao Início</a>
            <button type="submit" class="btn btn-primary px-4">Salvar Todas</button>
        </div>
    </form>
</div>

<script>
// Formata para o padrão de moeda brasileira
function formatarMoeda(valor) {
    valor = valor.replace(/\D/g, ''); // remove tudo que não é número
    if (valor.length === 0) return '';
    valor = (parseInt(valor) / 100).toFixed(2) + "";
    valor = valor.replace(".", ",");
    valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    return valor;
}

// Converte texto de moeda para float (ex: "1.234,56" -> 1234.56)
function moedaParaFloat(valor) {
    return parseFloat(valor.replace(/\./g, '').replace(',', '.')) || 0;
}

// Atualiza o campo de total por linha (colaborador)
function atualizarTotal(tr) {
    const campos = tr.querySelectorAll(".moeda");
    const total = tr.querySelector(".total");
    let soma = 0;
    campos.forEach(input => soma += moedaParaFloat(input.value));
    total.value = soma.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Aplica máscara de moeda e calcula total automaticamente
document.querySelectorAll(".moeda").forEach(input => {
    // Aplica a formatação inicial
    input.value = formatarMoeda(input.value.replace(/\D/g, ''));

    // Ao digitar no campo
    input.addEventListener("input", e => {
        let valor = e.target.value.replace(/\D/g, "");
        e.target.value = formatarMoeda(valor);
        atualizarTotal(e.target.closest("tr"));
    });

    // Atualiza o total ao carregar
    atualizarTotal(input.closest("tr"));
});

// Confirma envio do formulário
function confirmarEnvio() {
    return confirm("Tem certeza que deseja salvar todas as metas?");
}
</script>
</body>
</html>
