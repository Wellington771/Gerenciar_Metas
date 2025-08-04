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
  <div class="container-fluid px-4">
    <div class="d-flex w-100 align-items-center justify-content-between flex-wrap">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <!--<a class="navbar-brand" href="dashboard.php">Voltar ao menu inicial</a> -->
        <span style="color:#fff;font-weight:bold;"> <?php if (isset($_SESSION['usuario'])) echo htmlspecialchars($_SESSION['usuario']); ?> </span>
      </div>
      <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
        <a href="metas.php" class="btn btn-light fw-bold" id="btnDefinirMetas" style="min-width:130px;color:#388e3c;">Definir Metas</a>
        <a href="fechar_mes.php" class="btn btn-light fw-bold" id="btnFecharMes" style="min-width:130px;color:#388e3c;">Fechar Mês</a>
        <button type="button" class="btn btn-light fw-bold" id="btnAbrirImportar" data-bs-toggle="modal" data-bs-target="#modalImportar" style="min-width:150px;color:#388e3c;">Importar Arquivos</button>
        <a href="logout.php" class="btn btn-light fw-bold" style="min-width:80px;color:#388e3c;">Sair</a>
      </div>
    </div>
  </div>
</nav>
<div class="container mt-4">
  <!-- <form action="dashboard.php" method="post" enctype="multipart/form-data" class="mb-4">
    <div class="row g-2 align-items-center">
      <div class="col-auto">
        <input type="file" name="csv_file" accept=".xlsx" class="form-control" required>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-success">Importar arquivo</button>
      </div>
    </div>
  </form> -->
</div>
