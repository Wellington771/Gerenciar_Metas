<?php
// Inicia a sessão para armazenar dados do usuário durante a navegação
session_start();

// Se já existir um usuário logado na sessão, redireciona direto para dashboard.php
if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit; // Encerra o script para evitar que o restante do código seja executado
}

// Variável que armazenará a mensagem de erro para exibir na tela, caso login falhe
$erro = '';

// Verifica se o formulário foi enviado via método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura os valores enviados do formulário via POST,
    // caso não exista, atribui string vazia
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Conecta ao banco de dados
    require_once 'config/database.php';

    // Busca o usuário no banco
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE NomeUsuario = ? AND Ativo = 1 LIMIT 1');
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['SenhaHash'] === $senha) {
        // Login bem-sucedido
        $_SESSION['usuario'] = $usuario;
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = 'Usuário ou senha inválidos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Define a codificação de caracteres para UTF-8 -->
    <meta charset="UTF-8" />
    <!-- Define que o layout deve ajustar-se à largura do dispositivo e escala inicial -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - Dashboard Gerencial</title>

    <!-- Importa o CSS do Bootstrap 5 para estilos prontos e responsivos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Importa os ícones do Bootstrap Icons para usar nos inputs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

    <style>
        /* Estilo do body com gradiente de fundo suave em tons de verde pastel */
        body {
            height: 100vh; /* Altura total da viewport */
            background: linear-gradient(135deg, #d7ecd9, #f0f7f1);
            display: flex; /* Usado para centralizar o conteúdo */
            justify-content: center; /* Centraliza horizontalmente */
            align-items: center; /* Centraliza verticalmente */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; /* Remove margens padrão */
            padding: 1rem; /* Espaçamento interno para pequenas margens */
        }

        /* Estilo do card que contém o formulário */
        .card {
            width: 100%; /* Ocupar toda largura disponível */
            max-width: 400px; /* Limita largura máxima para telas maiores */
            border-radius: 1rem; /* Bordas arredondadas */
            box-shadow: 0 8px 25px rgba(56, 142, 60, 0.25); /* Sombra suave verde */
            background-color: #fff; /* Fundo branco */
            padding: 2.5rem 2rem; /* Espaçamento interno do conteúdo */
            animation: fadeInUp 0.8s ease forwards; /* Animação ao carregar */
            box-sizing: border-box; /* Inclui padding dentro da largura */
        }

        /* Definição da animação fadeInUp para o card */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px); /* Começa levemente deslocado para baixo */
            }
            to {
                opacity: 1;
                transform: translateY(0); /* Termina na posição normal */
            }
        }

        /* Estilo do título do card */
        h3 {
            color: #2e7d32; /* Verde escuro */
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 700;
            letter-spacing: 1px; /* Espaço entre letras */
        }

        /* Estilo do ícone dentro do grupo de input */
        .input-group-text {
            background-color: #c6dec4; /* Verde claro */
            color: #2e7d32; /* Verde escuro */
            border: none; /* Sem borda */
            border-radius: 0.5rem 0 0 0.5rem; /* Arredonda só os cantos da esquerda */
            font-size: 1.2rem; /* Ícone maior */
        }

        /* Estilo dos campos de entrada (inputs) */
        .form-control {
            border-radius: 0 0.5rem 0.5rem 0; /* Arredonda cantos da direita */
            border: 1px solid #c6dec4; /* Borda verde clara */
            transition: border-color 0.3s ease; /* Animação suave para mudança de borda */
        }

        /* Estilo do input quando está focado */
        .form-control:focus {
            border-color: #388e3c; /* Verde mais forte */
            box-shadow: 0 0 8px rgba(56, 142, 60, 0.4); /* Sombra verde ao redor */
            outline: none; /* Remove o contorno padrão */
        }

        /* Estilo do botão de login */
        .btn-success {
            background-color: #388e3c; /* Verde forte */
            border: none;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 0.6rem;
            border-radius: 0.6rem; /* Bordas arredondadas */
            transition: background-color 0.3s ease, box-shadow 0.3s ease; /* Transição suave */
        }

        /* Efeito ao passar o mouse no botão */
        .btn-success:hover {
            background-color: #2e7d32; /* Verde mais escuro */
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.4); /* Sombra mais intensa */
        }

        /* Estilo da mensagem de erro */
        .alert-danger {
            font-size: 0.9rem;
            border-radius: 0.6rem;
        }

        /* Estilo do rodapé fixo */
        footer {
            position: fixed;
            bottom: 10px; /* Distância da parte inferior da tela */
            width: 100%;
            text-align: center;
            color: #7a9a6a; /* Verde claro */
            font-size: 0.85rem;
            user-select: none; /* Impede seleção do texto */
            font-weight: 300;
        }

        /* Responsividade para telas menores que 480px (celulares) */
        @media (max-width: 480px) {
            .card {
                padding: 2rem 1.5rem; /* Menos padding nas laterais */
                border-radius: 1rem;
            }

            h3 {
                font-size: 1.5rem; /* Fonte menor no título */
            }

            .btn-success {
                font-size: 1rem;
                padding: 0.5rem; /* Botão menor para caber melhor */
            }
        }
    </style>
</head>
<body>

    <!-- Card central contendo o formulário de login -->
    <div class="card shadow-sm">
        <!-- Título do sistema -->
        <h3>Dashboard Gerencial</h3>

        <!-- Se existir erro, mostra o alerta com mensagem -->
        <?php if ($erro): ?>
            <div class="alert alert-danger" role="alert">
                <!-- Ícone de alerta antes da mensagem -->
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de login com método POST -->
        <form method="post" novalidate>
            <!-- Campo usuário com label e ícone -->
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuário</label>
                <div class="input-group">
                    <!-- Ícone do usuário -->
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <!-- Input para o nome do usuário -->
                    <input type="text" class="form-control" id="usuario" name="usuario" required autofocus placeholder="Digite seu usuário" />
                </div>
            </div>

            <!-- Campo senha com label e ícone -->
            <div class="mb-4">
                <label for="senha" class="form-label">Senha</label>
                <div class="input-group">
                    <!-- Ícone de cadeado -->
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <!-- Input para senha -->
                    <input type="password" class="form-control" id="senha" name="senha" required placeholder="Digite sua senha" />
                </div>
            </div>

            <!-- Botão de envio do formulário -->
            <button type="submit" class="btn btn-success w-100">Entrar</button>
        </form>
    </div>

    <!-- Rodapé fixo com copyright -->
    <footer>
        &copy; <?php echo date('Y'); ?> Dashboard Gerencial
    </footer>

</body>
</html>
