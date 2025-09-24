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
  `SenhaHash` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Senha criptografada',
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
  PRIMARY KEY (`ColaboradorID`),
  INDEX `idx_colaborador_codigo` (`CodigoExterno`),
  INDEX `idx_colaborador_email` (`Email`),
  INDEX `idx_colaborador_vendas` (`ValorAtualVendas` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Tabela de histórico de vendas
-- ============================
CREATE TABLE `HistoricoVendas` (
  `HistoricoID` INT NOT NULL AUTO_INCREMENT,
  `ColaboradorID` INT NOT NULL,
  `ValorVenda` DECIMAL(12,2) NOT NULL,
  `Categoria` VARCHAR(50) NOT NULL COMMENT 'Maquiagem, Skin Care, Produtos Gerais',
  `DataVenda` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `InfoCSV` TEXT DEFAULT NULL COMMENT 'Informações adicionais do CSV importado',
  `CodigoRevendedor` VARCHAR(20) DEFAULT NULL COMMENT 'Código do revendedor',
  `Revendedor` VARCHAR(100) DEFAULT NULL COMMENT 'Nome do revendedor',
  PRIMARY KEY (`HistoricoID`),
  FOREIGN KEY (`ColaboradorID`) REFERENCES `colaboradores`(`ColaboradorID`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_historico_colaborador` (`ColaboradorID`),
  INDEX `idx_historico_data` (`DataVenda`),
  INDEX `idx_historico_categoria` (`Categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Tabela de usuários
-- ============================
CREATE TABLE `usuarios` (
  `UsuarioID` INT NOT NULL AUTO_INCREMENT,
  `NomeUsuario` VARCHAR(50) NOT NULL UNIQUE,
  `SenhaHash` VARCHAR(255) NOT NULL,
  `NivelAcesso` ENUM('Admin', 'Usuario') NOT NULL DEFAULT 'Usuario',
  `Ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `DataCriacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UltimoLogin` DATETIME DEFAULT NULL,
  PRIMARY KEY (`UsuarioID`),
  INDEX `idx_usuario_nome` (`NomeUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Tabela de configurações
-- ============================
CREATE TABLE `configuracao` (
  `Chave` VARCHAR(50) NOT NULL,
  `Valor` VARCHAR(255) NOT NULL,
  `Descricao` VARCHAR(255) DEFAULT NULL,
  `DataAtualizacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Tabelas para funcionalidades futuras
-- ============================
CREATE TABLE `produtos` (
  `ProdutoID` INT NOT NULL AUTO_INCREMENT,
  `NomeProduto` VARCHAR(100) NOT NULL,
  `Categoria` VARCHAR(100),
  `PrecoUnitario` DECIMAL(12,2),
  PRIMARY KEY (`ProdutoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `revendedores` (
  `RevendedorID` INT NOT NULL AUTO_INCREMENT,
  `Nome` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`RevendedorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================
-- Dados iniciais
-- ============================
INSERT INTO `colaboradores`
(`NomeCompleto`, `Email`, `SenhaHash`, `NivelAcesso`, `Ativo`, `CodigoExterno`, `ValorAtualVendas`, `MetaMensal`, `MetaMaquiagem`, `MetaSkinCare`, `MetaProdutosGerais`)
VALUES
('Ana Paula', 'ana@example.com', '', 'Colaborador', 1, '18889218', 0.00, 0.00, 0.00, 0.00, 0.00),
('Cirleide', 'cirleide@example.com', '', 'Colaborador', 1, '18889165', 0.00, 0.00, 0.00, 0.00, 0.00),
('Luziane', 'luziane@example.com', '', 'Colaborador', 1, '11784209', 0.00, 0.00, 0.00, 0.00, 0.00),
('Camila', 'camila@example.com', '', 'Colaborador', 1, '20293243', 0.00, 0.00, 0.00, 0.00, 0.00);

INSERT INTO `usuarios` (`NomeUsuario`, `SenhaHash`, `NivelAcesso`, `Ativo`)
VALUES ('admin', 'admin', 'Admin', 1);

INSERT INTO `usuarios` (`NomeUsuario`, `SenhaHash`, `NivelAcesso`, `Ativo`)
VALUES ('treinamento', 'treinamento', 'Usuario', 1);

INSERT INTO `configuracao` (`Chave`, `Valor`, `Descricao`)
VALUES 
('sistema_nome', 'Dashboard Gerencial', 'Nome do sistema'),
('versao', '1.0', 'Versão atual do sistema'),
('mes_atual', DATE_FORMAT(NOW(), '%Y-%m'), 'Mês de referência atual');
