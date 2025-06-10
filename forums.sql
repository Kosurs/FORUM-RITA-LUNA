-- Criação da tabela de usuários para MySQL/MariaDB
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0, -- 1 para administradores, 0 para usuários comuns
    is_banned TINYINT(1) DEFAULT 0 -- 1 para banido, 0 para normal
);

-- Inserts de usuários iniciais (migrados do users.sql)
INSERT INTO users (username, password, email) VALUES
('admin', 'admin_password', 'admin@example.com'),
('user', 'user_password', 'user@example.com');

-- Criação da tabela de fóruns (subfóruns)
CREATE TABLE forums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Criação da tabela de posts (já deve existir, mas aqui com forum_id)
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    forum_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Criação da tabela de comentários
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ALTER TABLE para migração em bancos já existentes:
-- ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0;
-- ALTER TABLE users ADD COLUMN is_banned TINYINT(1) DEFAULT 0;
