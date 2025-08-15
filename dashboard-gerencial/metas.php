<?php
// Inicia a sessão do usuário
session_start();

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

// Importa a conexão com o banco de dados
require_once 'config/database.php';

// Função auxiliar para converter valores de moeda brasileira (ex: "1.234,56") para float.
function moedaParaFloatPHP($valor) {
    $valor = str_replace('.', '', $valor); // remove os pontos (separador de milhar)
    $valor = str_replace(',', '.', $valor); // substitui a vírgula por ponto (separador decimal)
    return floatval($valor); // converte a string para um número float
}

// Se o formulário foi enviado com dados dos colaboradores via método POST...
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['colaboradores'])) {
    // Percorre cada colaborador enviado no formulário
    foreach ($_POST['colaboradores'] as $id => $dados) {
        // Converte os valores das metas de maquiagem, skincare e produtos gerais para float
        $maquiagem = moedaParaFloatPHP($dados['meta_maquiagem']);
        $skincare = moedaParaFloatPHP($dados['meta_skincare']);
        $produtosGerais = moedaParaFloatPHP($dados['meta_produtos_gerais']);

        // Calcula a meta total somando todas as metas individuais
        $metaTotal = $maquiagem + $skincare + $produtosGerais;

        // Prepara e executa a atualização no banco de dados para o colaborador atual
        $stmt = $pdo->prepare("UPDATE Colaboradores 
            SET MetaMaquiagem = ?, MetaSkinCare = ?, MetaProdutosGerais = ?, MetaMensal = ? 
            WHERE ColaboradorID = ?");
        $stmt->execute([$maquiagem, $skincare, $produtosGerais, $metaTotal, $id]);
    }

    // Após a atualização, redireciona o usuário de volta para o painel principal (dashboard)
    header("Location: dashboard.php");
    exit;
}

// Consulta todos os colaboradores do banco de dados, ordenando pelo nome
$colaboradores = $pdo->query("SELECT * FROM Colaboradores ORDER BY NomeCompleto")->fetchAll();

// Inclui o arquivo de cabeçalho, que contém o menu lateral, o cabeçalho superior e o CSS base.
// Isso garante um layout consistente em todas as páginas.
require_once 'includes/header.php';
?>

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
// Função para formatar um número como moeda brasileira (ex: 123456 -> "1.234,56")
function formatarMoeda(valor) {
    valor = valor.replace(/\D/g, ''); // Remove tudo que não for dígito
    if (valor.length === 0) return ''; // Retorna string vazia se não houver valor
    valor = (parseInt(valor) / 100).toFixed(2) + ""; // Divide por 100 e formata com 2 casas decimais
    valor = valor.replace(".", ","); // Substitui o ponto por vírgula para o separador decimal
    valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, "."); // Adiciona pontos como separadores de milhar
    return valor;
}

// Função para converter uma string de moeda ("1.234,56") para um número float (1234.56)
function moedaParaFloat(valor) {
    return parseFloat(valor.replace(/\./g, '').replace(',', '.')) || 0;
}

// Função para atualizar o campo de total em uma linha da tabela
function atualizarTotal(tr) {
    const campos = tr.querySelectorAll(".moeda"); // Seleciona todos os campos de meta da linha
    const total = tr.querySelector(".total"); // Seleciona o campo de total da linha
    let soma = 0;
    campos.forEach(input => soma += moedaParaFloat(input.value)); // Soma os valores de todos os campos de meta
    total.value = soma.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); // Formata e exibe o total
}

// Aplica a máscara de moeda e o cálculo do total a cada campo de input com a classe "moeda"
document.querySelectorAll(".moeda").forEach(input => {
    // Ao carregar, aplica a formatação inicial e atualiza o total da linha
    input.value = formatarMoeda(input.value.replace(/\D/g, ''));
    atualizarTotal(input.closest("tr"));

    // Adiciona um evento que dispara a cada tecla pressionada no campo de input
    input.addEventListener("input", e => {
        let valor = e.target.value.replace(/\D/g, ""); // Remove caracteres não numéricos
        e.target.value = formatarMoeda(valor); // Aplica a formatação de moeda
        atualizarTotal(e.target.closest("tr")); // Recalcula e atualiza o total da linha
    });
});

// Exibe uma caixa de confirmação antes de enviar o formulário
function confirmarEnvio() {
    return confirm("Tem certeza que deseja salvar todas as metas?");
}
</script>
</body>
</html>