CREATE DATABASE IF NOT EXISTS sierra_creative
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sierra_creative;

CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  perfil ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE solicitacoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  telefone VARCHAR(20) NOT NULL,
  tipo_servico ENUM('videomaker','storymaker') NOT NULL,
  mensagem TEXT NOT NULL,
  status ENUM('pendente','em_andamento','concluida','cancelada')
    NOT NULL DEFAULT 'pendente',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_solicitacao_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE,
  INDEX idx_solicitacoes_usuario (usuario_id),
  INDEX idx_solicitacoes_status (status)
) ENGINE=InnoDB;