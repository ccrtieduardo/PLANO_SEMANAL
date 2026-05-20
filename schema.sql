-- Criação do banco
CREATE DATABASE IF NOT EXISTS plano_semanal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE plano_semanal;

-- Tabela de usuários
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    role ENUM('admin','professor','coordenador') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Turmas (6º ao 9º ano e 1ª a 3ª série)
CREATE TABLE turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    segmento ENUM('fundamental','medio') NOT NULL,
    ano VARCHAR(20) NOT NULL
);

-- Disciplinas
CREATE TABLE disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
);

-- Relação disciplinas x turma (currículo específico)
CREATE TABLE class_disciplinas (
    turma_id INT NOT NULL,
    disciplina_id INT NOT NULL,
    PRIMARY KEY (turma_id, disciplina_id),
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE
);

-- Atribuição de turmas ao professor
CREATE TABLE teacher_classes (
    teacher_id INT NOT NULL,
    turma_id INT NOT NULL,
    PRIMARY KEY (teacher_id, turma_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
);

-- Atribuição de disciplinas ao professor
CREATE TABLE teacher_subjects (
    teacher_id INT NOT NULL,
    disciplina_id INT NOT NULL,
    PRIMARY KEY (teacher_id, disciplina_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE
);

-- Planos semanais (cada linha = um dia/atividade)
CREATE TABLE planos_semanais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina_id INT NOT NULL,
    bimestre TINYINT NOT NULL CHECK (bimestre BETWEEN 1 AND 4),
    data DATE NOT NULL,
    pagina VARCHAR(50),
    o_que TEXT,
    como TEXT,
    recursos TEXT,
    p_casa TEXT,
    status ENUM('pendente','aprovado','revisao') DEFAULT 'pendente',
    coordinator_comment TEXT,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Inserção das turmas
INSERT INTO turmas (nome, segmento, ano) VALUES
('6º ano', 'fundamental', '6'),
('7º ano', 'fundamental', '7'),
('8º ano', 'fundamental', '8'),
('9º ano', 'fundamental', '9'),
('1ª série', 'medio', '1'),
('2ª série', 'medio', '2'),
('3ª série', 'medio', '3');

-- Disciplinas (todas as ocorrências)
INSERT INTO disciplinas (nome) VALUES
('Artes'),
('Ciências'),
('Educação Física'),
('Geografia'),
('História'),
('Inglês'),
('Português'),
('Matemática'),
('Projeto Integrador'),
('Produção de Texto'),
('Literatura e Compreensão'),
('Biologia'),
('Física'),
('Filosofia'),
('Matemática Aplicada'),
('Projeto de Vida'),
('Química'),
('Redação'),
('Sociologia'),
('Oficina de Texto');

-- Relação turma-disciplina (currículos)
-- 6º ao 9º ano (fundamental)
INSERT INTO class_disciplinas (turma_id, disciplina_id)
SELECT t.id, d.id FROM turmas t, disciplinas d
WHERE t.segmento = 'fundamental'
  AND d.nome IN ('Artes','Ciências','Educação Física','Geografia','História','Inglês','Português','Matemática','Projeto Integrador','Produção de Texto','Literatura e Compreensão');

-- 1ª e 2ª série (médio)
INSERT INTO class_disciplinas (turma_id, disciplina_id)
SELECT t.id, d.id FROM turmas t, disciplinas d
WHERE t.nome IN ('1ª série', '2ª série')
  AND d.nome IN ('Artes','Biologia','Educação Física','Física','Filosofia','Geografia','História','Inglês','Matemática','Matemática Aplicada','Português','Projeto de Vida','Química','Redação','Sociologia');

-- 3ª série (médio) – troca Redação por Oficina de Texto
INSERT INTO class_disciplinas (turma_id, disciplina_id)
SELECT t.id, d.id FROM turmas t, disciplinas d
WHERE t.nome = '3ª série'
  AND d.nome IN ('Artes','Biologia','Educação Física','Física','Filosofia','Geografia','História','Inglês','Matemática','Matemática Aplicada','Português','Projeto de Vida','Química','Oficina de Texto','Sociologia');

-- Usuário administrador padrão (senha: admin123)
-- IMPORTANTE: Execute o PHP para gerar o hash correto antes de inserir.
-- Exemplo: $hash = password_hash('admin123', PASSWORD_DEFAULT);
-- Depois substitua '$2y$10$...' pelo hash gerado.
INSERT INTO users (nome, email, senha, role) VALUES
('Administrador', 'admin@escola.com', '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', 'admin');
-- (A hash acima é apenas placeholder; recomenda-se gerar a sua própria)