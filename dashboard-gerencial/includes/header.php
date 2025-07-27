<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #388e3c;">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Dashboard Gerencial</a>
    <span class="navbar-text ms-3" style="color:#fff;font-weight:bold;">
      <?php if (isset($_SESSION['usuario'])) echo htmlspecialchars($_SESSION['usuario']); ?>
    </span>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="metas.php">Definir Metas</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="fechar_mes.php">Fechar Mês</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Sair</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div class="container mt-4">
  <form action="dashboard.php" method="post" enctype="multipart/form-data" class="mb-4">
    <div class="row g-2 align-items-center">
      <div class="col-auto">
        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-success">Enviar CSV</button>
      </div>
    </div>
  </form>
</div>
