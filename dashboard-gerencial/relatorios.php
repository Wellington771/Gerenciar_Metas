<?php
<?php
include 'includes/header.php'; // Inclui menu e navbar

// Conexão com o banco de dados
$host = 'localhost';
$usuario_db = 'root';
$senha_db = '';
$banco = 'gerenciadormetasdb';

$conn = new mysqli($host, $usuario_db, $senha_db, $banco);
if ($conn->connect_error) {
    die('Erro de conexão: ' . $conn->connect_error);
}

// Resumo de vendas do mês atual
$mesAtual = date('Y-m-01');
$sql = "SELECT p.NomeProduto, SUM(h.ValorVenda) as TotalVendido, COUNT(h.HistoricoID) as QtdeVendas
        FROM historicovendas h
        LEFT JOIN produtos p ON h.ProdutoID = p.ProdutoID
        WHERE h.DataVenda >= ?
        GROUP BY h.ProdutoID
        ORDER BY p.NomeProduto";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $mesAtual);
$stmt->execute();
$resumo = $stmt->get_result();
?>
<div class="container mt-5">
  <div class="card shadow">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Relatórios de Vendas</h4>
      <a href="dashboard.php" class="btn btn-light btn-sm">⬅️ Voltar ao Início</a>
    </div>
    <div class="card-body">
      <p>Você pode gerar e baixar o relatório com os dados mais recentes das vendas.</p>
      <form method="post" action="gerar_relatorio.php">
        <button type="submit" class="btn btn-success">📥 Baixar Relatório Atual</button>
      </form>
      <hr>
      <h5 class="mt-4 mb-3">Resumo das Vendas do Mês</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Produto</th>
            <th>Quantidade de Vendas</th>
            <th>Total Vendido (R$)</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $resumo->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['NomeProduto']) ?></td>
              <td><?= $row['QtdeVendas'] ?></td>
              <td><?= number_format($row['TotalVendido'], 2, ',', '.') ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>