<?php
include 'includes/header.php'; // Inclui menu e navbar
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
    </div>
  </div>
</div>

</body>
</html>
