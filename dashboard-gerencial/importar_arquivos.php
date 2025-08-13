<!DOCTYPE html>
<html lang="pt-br"> 
<head>
    <meta charset="UTF-8"> <!-- Define a codificação de caracteres como UTF-8 -->
    <title>Importar Arquivos</title> <!-- Título da aba do navegador -->

    <!-- Estilos CSS da página -->
    <style>
        /* Estilo geral da página */
        body {
            font-family: 'Segoe UI', sans-serif; /* Fonte moderna */
            background-color: #f0f9f3; /* Cor de fundo clara */
            margin: 0;
            padding: 0;
        }

        /* Container central da página */
        .container {
            max-width: 600px; /* Largura máxima */
            margin: 40px auto; /* Centraliza vertical e horizontalmente */
            background: #ffffff; /* Fundo branco */
            border-radius: 10px; /* Cantos arredondados */
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); /* Sombra leve */
            padding: 30px;
            border: 1px solid #e1e1e1; /* Borda cinza clara */
        }

        /* Título principal */
        h2 {
            text-align: center; /* Centraliza o texto */
            color: #2e7d32; /* Cor verde escura */
            margin-bottom: 30px; /* Espaçamento inferior */
        }

        /* Títulos de seção (Maquiagem, Skin Care, Eudora) */
        h3 {
            color: #2e7d32;
            border-bottom: 2px solid #2e7d32; /* Linha verde abaixo do título */
            padding-bottom: 5px;
            margin-top: 30px;
        }

        /* Estilo dos blocos de cada item com botão */
        .item {
            display: flex; /* Layout flexível (lado a lado) */
            justify-content: space-between; /* Espaço entre os itens */
            align-items: center; /* Alinha verticalmente */
            background: #f1f8f4; /* Cor de fundo suave */
            padding: 12px 20px;
            margin-top: 12px;
            border-radius: 8px;
            transition: 0.2s; /* Efeito de transição */
        }

        /* Efeito hover para mudar cor ao passar o mouse */
        .item:hover {
            background: #e8f5e9;
        }

        /* Estilo do texto dentro dos itens */
        .item span {
            font-weight: 500;
            color: #333;
        }

        /* Estilo do botão "Importar" */
        .btn {
            background: #43a047; /* Verde */
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Sombra sutil */
        }

        /* Cor do botão ao passar o mouse */
        .btn:hover {
            background: #388e3c;
        }

        /* Estilo do botão "Voltar ao Início" */
        .btn-voltar {
            background-color: #616161; /* Cinza escuro */
            display: block;
            margin: 30px auto 0; /* Centralizado */
            padding: 12px 25px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            font-size: 15px;
            text-align: center;
            text-decoration: none;
            border: none;
        }

        /* Cor do botão "Voltar" ao passar o mouse */
        .btn-voltar:hover {
            background-color: #424242;
        }

        /* Oculta o campo de input file */
        input[type="file"] {
            display: none;
        }
    </style>
</head>
<body>

    <!-- Estrutura principal da página -->
    <div class="container">

        <!-- Título principal da página -->
        <h2>Importar Arquivos por Categorias</h2>

        <!-- Seção Maquiagem -->
        <h3>Maquiagem</h3>

        <!-- Produto: Eudora - 23178 -->
        <div class="item">
            <span>Eudora - 23178</span>
            <label class="btn">
                Importar
                <input type="file" onchange="handleFile(this)"> <!-- Input oculto, ativado pelo botão -->
            </label>
        </div>

        <!-- Produto: Boti - 93 -->
        <div class="item">
            <span>Boti - 93</span>
            <label class="btn">
                Importar
                <input type="file" onchange="handleFile(this)">
            </label>
        </div>

        <!-- Produto: QDB - 38503 -->
        <div class="item">
            <span>QDB - 38503</span>
            <label class="btn">
                Importar
                <input type="file" onchange="handleFile(this)">
            </label>
        </div>

        <!-- Seção Skin Care -->
        <h3>Skin Care</h3>

        <div class="item">
            <span>Eudora - 23132</span>
            <label class="btn">
                Importar
                <input type="file" onchange="handleFile(this)">
            </label>
        </div>

        <div class="item">
            <span>Boti - 1556</span>
            <label class="btn">
                Importar
                <input type="file" onchange="handleFile(this)">
            </label>
        </div>

        <div class="item">
            <span>QDB - 38599</span>
            <label class="btn">
                Importar
                <input type="file" onchange="handleFile(this)">
            </label>
        </div>

        <!-- Nova seção: Eudora - Produtos -->
        <h3>Eudora</h3>

        <div class="item">
            <span>Produtos</span>
            <label class="btn">
                Importar
                <input type="file" onchange="handleFile(this)">
            </label>
        </div>

        <!-- Botão para voltar ao início -->
        <button onclick="window.location.href='index.php'" class="btn-voltar">Voltar ao Início</button>

    </div>

    <!-- Script para lidar com o input de arquivos -->
    <script>
        function handleFile(input) {
            const file = input.files[0]; // Pega o arquivo selecionado
            if (file) {
                alert(`Arquivo "${file.name}" selecionado com sucesso!`);
                // Aqui pode ser adicionada lógica para upload via AJAX ou formulário
            }
        }
    </script>
</body>
</html>
