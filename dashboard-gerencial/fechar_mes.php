<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado; caso contrário, redireciona para a página de login
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

// Conexão com o banco de dados
$host = 'localhost';
$usuario_db = 'root';
$senha_db = '';
$banco = 'gerenciadormetasdb';

$conn = new mysqli($host, $usuario_db, $senha_db, $banco);
if ($conn->connect_error) {
    die('Erro de conexão: ' . $conn->connect_error);
}

$confirmado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    // Remove vendas do mês atual
    $mesAtual = date('Y-m-01');
    $sql = "DELETE FROM historicovendas WHERE DataVenda >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $mesAtual);
    $stmt->execute();
    $stmt->close();

    $confirmado = true;
    header("refresh:3;url=dashboard.php");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Fechar Mês - Rumo à Meta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .card-custom { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .btn-primary { background-color: #388e3c; border-color: #388e3c; }
        .btn-primary:hover { background-color: #2e7d32; }
        .btn-danger { background-color: #d32f2f; border-color: #d32f2f; }
        .btn-danger:hover { background-color: #b71c1c; }
    </style>
</head>
<body>
<!-- Cabeçalho da aplicação -->
<?php include 'includes/header.php'; ?>

<!-- Conteúdo principal -->
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-custom p-4 bg-white">
                <h4 class="mb-3 text-center text-success">Fechamento de Mês</h4>

                <?php if ($confirmado): ?>
                    <!-- Mensagem de sucesso após remover as vendas do mês atual -->
                    <div class="alert alert-success text-center">
                        ✅ Todas as vendas do mês foram removidas com sucesso!<br>
                        Redirecionando para o painel...
                    </div>
                <?php else: ?>
                    <!-- Instruções e botões -->
                    <p class="text-center">
                        Esta ação irá <strong>remover todas as vendas do mês atual</strong> de todos os colaboradores,
                        dando início a um novo ciclo comercial.
                    </p>
                    <div class="text-center">
                        <!-- Botão que abre o modal de confirmação -->
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmModal">
                            Fechar Mês
                        </button>
                        <!-- Link para cancelar e voltar ao dashboard -->
                        <a href="dashboard.php" class="btn btn-secondary ms-2">Voltar ao Início</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <!-- Formulário de confirmação que envia POST para esta mesma página -->
        <form method="post">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmModalLabel">Confirmar Fechamento do Mês</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Ao prosseguir, todas as vendas do mês atual serão removidas, iniciando um novo ciclo.<br>
                <strong>Esta ação é irreversível.</strong>
            </div>
            <div class="modal-footer">
                <!-- Botão para confirmar a operação -->
                <button type="submit" name="confirmar" class="btn btn-danger">Sim, Fechar Mês</button>
                <!-- Botão para cancelar o modal -->
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
  </div>
</div>

<!-- Rodapé da aplicação -->
<?php include 'includes/footer.php'; ?>

<!-- JS do Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
