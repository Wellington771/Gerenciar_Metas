-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS GerenciadorMetasDB;
USE GerenciadorMetasDB;

-- Tabela Colaboradores
CREATE TABLE IF NOT EXISTS Colaboradores (
    ColaboradorID INT AUTO_INCREMENT PRIMARY KEY,
    NomeCompleto VARCHAR(100) NOT NULL,
    CodigoExterno VARCHAR(20) NOT NULL UNIQUE,
    ValorAtualVendas DECIMAL(12,2) NOT NULL DEFAULT 0,
    MetaMensal DECIMAL(12,2) NOT NULL DEFAULT 0,
    MetaMaquiagem DECIMAL(12,2) NOT NULL DEFAULT 0,
    MetaSkinCare DECIMAL(12,2) NOT NULL DEFAULT 0,
    MetaProdutosGerais DECIMAL(12,2) NOT NULL DEFAULT 0
    -- Categoria VARCHAR(30) -- opcional, caso queira categorizar colaboradores
);

-- Tabela HistoricoVendas
CREATE TABLE IF NOT EXISTS HistoricoVendas (
    VendaID INT AUTO_INCREMENT PRIMARY KEY,
    ColaboradorID INT NOT NULL,
    DataVenda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ValorVenda DECIMAL(12,2) NOT NULL,
    Categoria VARCHAR(30) NOT NULL,
    InfoCSV TEXT,
    FOREIGN KEY (ColaboradorID) REFERENCES Colaboradores(ColaboradorID) ON DELETE CASCADE
);

-- Populando Colaboradores
INSERT INTO Colaboradores (NomeCompleto, CodigoExterno, MetaMensal, MetaMaquiagem, MetaSkinCare, MetaProdutosGerais) VALUES
('Ana Paula', '18889218', 10000, 4000, 3000, 3000),
('Cirleide', '18889165', 12000, 5000, 4000, 3000),
('Luziane', '11784209', 9000, 3000, 3000, 3000),
('Camila', '20293243', 11000, 4000, 4000, 3000);

-- Exemplo de vendas por categoria
INSERT INTO HistoricoVendas (ColaboradorID, ValorVenda, Categoria, InfoCSV) VALUES
(1, 2000, 'maquiagem', 'CSV info'),
(1, 1500, 'skin care', 'CSV info'),
(2, 3000, 'produtos gerais', 'CSV info');
