-- AVISO: Este arquivo foi unificado em forums.sql
-- Use apenas forums.sql para criar todas as tabelas do sistema.
-- Adicione a coluna de banimento:
-- ALTER TABLE users ADD COLUMN is_banned TINYINT(1) DEFAULT 0;

-- users.sql: Criação da tabela de usuários para MySQL/MariaDB

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_banned TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, password, email) VALUES
('admin', 'admin_password', 'admin@example.com'),
('user', 'user_password', 'user@example.com');

SELECT * FROM users;

-- Fim do script de criação da tabela de usuários
