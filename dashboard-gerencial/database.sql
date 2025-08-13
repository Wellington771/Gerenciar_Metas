-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS `gerenciadormetasdb`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `gerenciadormetasdb`;

-- ============================
-- Tabela de colaboradores
-- ============================
CREATE TABLE `colaboradores` (
  `ColaboradorID` INT NOT NULL AUTO_INCREMENT COMMENT 'Chave primária',
  `NomeCompleto` VARCHAR(100) NOT NULL COMMENT 'Nome completo do colaborador',
  `Email` VARCHAR(100) NOT NULL UNIQUE COMMENT 'E-mail único para login',
  `SenhaHash` VARCHAR(255) NOT NULL COMMENT 'Senha criptografada',
  `NivelAcesso` ENUM('Admin', 'Colaborador') NOT NULL DEFAULT 'Colaborador' COMMENT 'Nível de permissão',
  `Ativo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = bloqueado, 1 = ativo',
  `CodigoExterno` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código externo ou identificador',
  `ValorAtualVendas` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de vendas atual',
  `MetaMensal` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Meta mensal total',
  `MetaMaquiagem` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Meta para maquiagem',
  `MetaSkinCare` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Meta para skin care',
  `MetaProdutosGerais` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Meta para produtos gerais',
  `DataCadastro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data do cadastro',
  `UltimoLogin` DATETIME DEFAULT NULL COMMENT 'Último acesso ao sistema',
  PRIMARY KEY (`ColaboradorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Tabela de revendedores
-- ============================
CREATE TABLE `revendedores` (
  `RevendedorID` INT NOT NULL AUTO_INCREMENT,
  `Nome` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`RevendedorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Tabela de produtos
-- ============================
CREATE TABLE `produtos` (
  `ProdutoID` INT NOT NULL AUTO_INCREMENT,
  `NomeProduto` VARCHAR(100) NOT NULL,
  `Categoria` VARCHAR(100),
  `PrecoUnitario` DECIMAL(12,2),
  PRIMARY KEY (`ProdutoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Tabela de configuração
-- ============================
CREATE TABLE `configuracao` (
  `Chave` VARCHAR(50) NOT NULL,
  `Valor` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`Chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Tabela de metas
-- ============================
CREATE TABLE `metas` (
  `MetaID` INT NOT NULL AUTO_INCREMENT,
  `ColaboradorID` INT,
  `RevendedorID` INT,
  `ProdutoID` INT,
  `MesReferencia` DATE NOT NULL,
  `ValorMeta` DECIMAL(12,2) NOT NULL,
  `ValorPraticado` DECIMAL(12,2) DEFAULT 0.00,
  PRIMARY KEY (`MetaID`),
  FOREIGN KEY (`ColaboradorID`) REFERENCES `colaboradores`(`ColaboradorID`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`RevendedorID`) REFERENCES `revendedores`(`RevendedorID`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`ProdutoID`) REFERENCES `produtos`(`ProdutoID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Tabela de histórico de vendas
-- Esta tabela agora inclui a coluna 'Categoria'
-- ============================
CREATE TABLE `historicovendas` (
  `HistoricoID` INT NOT NULL AUTO_INCREMENT,
  `ColaboradorID` INT NOT NULL,
  `RevendedorID` INT,
  `ProdutoID` INT,
  `ValorVenda` DECIMAL(12,2) NOT NULL,
  `DataVenda` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Categoria` VARCHAR(100) DEFAULT NULL COMMENT 'Categoria do produto na venda',
  PRIMARY KEY (`HistoricoID`),
  FOREIGN KEY (`ColaboradorID`) REFERENCES `colaboradores`(`ColaboradorID`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`RevendedorID`) REFERENCES `revendedores`(`RevendedorID`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`ProdutoID`) REFERENCES `produtos`(`ProdutoID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Dados iniciais de colaboradores
-- ============================
INSERT INTO `colaboradores`
(`NomeCompleto`, `Email`, `SenhaHash`, `NivelAcesso`, `Ativo`, `CodigoExterno`, `ValorAtualVendas`, `MetaMensal`, `MetaMaquiagem`, `MetaSkinCare`, `MetaProdutosGerais`)
VALUES
('Ana Paula', 'ana@example.com', '', 'Colaborador', 1, '18889218', 0.00, 0.00, 0.00, 0.00, 0.00),
('Cirleide', 'cirleide@example.com', '', 'Colaborador', 1, '18889165', 0.00, 0.00, 0.00, 0.00, 0.00),
('Luziane', 'luziane@example.com', '', 'Colaborador', 1, '11784209', 0.00, 0.00, 0.00, 0.00, 0.00),
('Camila', 'camila@example.com', '', 'Colaborador', 1, '20293243', 0.00, 0.00, 0.00, 0.00, 0.00);