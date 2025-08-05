<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Menu Lateral</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    /* Navbar verde escuro */
    .navbar {
      background-color: #2e7d32 !important;
    }
    /* Texto "Bem vindo" */
    .usuario-texto {
      color: white;
      font-weight: bold;
      font-size: 1.1rem;
    }

    /* Sidebar - começa fora da tela */
    #sidebarMenu {
      position: fixed;
      top: 0;
      left: -250px; /* escondido à esquerda */
      width: 250px;
      height: 100vh;
      background-color: #2e7d32;
      color: white;
      padding-top: 60px;
      transition: left 0.3s ease;
      z-index: 1050;
      overflow-y: auto;
    }

    /* Sidebar aberto */
    #sidebarMenu.active {
      left: 0;
    }

    /* Fundo escurecido atrás do menu */
    #sidebarOverlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0,0,0,0.5);
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease;
      z-index: 1040;
    }
    #sidebarOverlay.active {
      opacity: 1;
      visibility: visible;
    }

    /* Estilo links da sidebar */
    #sidebarMenu a {
      color: white;
      font-weight: 600;
      display: block;
      padding: 15px 25px;
      text-decoration: none;
      border-bottom: 1px solid #388e3c;
      transition: background 0.2s ease;
    }
    #sidebarMenu a:hover {
      background: #388e3c;
      color: #e8f5e9;
    }

    /* Botão Menu */
    #btnToggleSidebar {
      background: white;
      color: #2e7d32;
      font-weight: bold;
      border: none;
      padding: 8px 20px;
      border-radius: 4px;
    }
    #btnToggleSidebar:hover {
      background: #388e3c;
      color: white;
      cursor: pointer;
    }

    /* Container navbar */
    .navbar-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      padding: 10px 20px;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-dark">
  <div class="container-fluid navbar-container">
    <button id="btnToggleSidebar">Menu</button>
    <div class="usuario-texto">Bem Vindo, <?php echo htmlspecialchars($_SESSION['usuario']); ?></div>
  </div>
</nav>

<!-- Sidebar -->
<div id="sidebarMenu">
  <a href="adicionar_colaborador.php">Adicionar Colaborador</a>
  <a href="metas.php">Definir Metas</a>
  <a href="fechar_mes.php">Fechar Mês</a>
  <a href="#" data-bs-toggle="modal" data-bs-target="#modalImportar">Importar Arquivos</a>
  <a href="logout.php">Sair</a>
</div>

<!-- Overlay -->
<div id="sidebarOverlay"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const btnToggle = document.getElementById('btnToggleSidebar');
  const sidebar = document.getElementById('sidebarMenu');
  const overlay = document.getElementById('sidebarOverlay');

  btnToggle.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  });

  overlay.addEventListener('click', () => {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
  });
</script>

</body>
</html>
