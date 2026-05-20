# 📘 Sistema de Planos Semanais – Colégio Educacional Christo Rei

Sistema web em PHP para substituir as planilhas de Excel no gerenciamento dos planos de aula semanais do colégio.  
Centraliza o planejamento pedagógico em uma plataforma modular com perfis de acesso para **professores**, **coordenadores** e **administradores**.

## ✨ Funcionalidades

- **Dashboard público**  
  Visualização dos planos organizados por turma, bimestre e disciplina, com agrupamento por professor.

- **Área do professor**  
  Criação, edição e exclusão dos planos de aula (campos: Data, Página, O Que, Como, Recursos, P/ Casa).

- **Painel do coordenador**  
  Avaliação individual de cada registro com status (Pendente, Aprovado, Revisão) e comentários.

- **Administração completa**  
  Gerenciamento de usuários (CRUD), definição de perfis (`admin`, `professor`, `coordenador`) e atribuição de disciplinas e turmas a cada professor.

- **Currículos personalizados**  
  - Ensino Fundamental (6º ao 9º ano): 11 disciplinas  
  - Ensino Médio 1ª e 2ª séries: 15 disciplinas  
  - 3ª série: substitui **Redação** por **Oficina de Texto**

- **Arquitetura modular**  
  Cada página com seu próprio arquivo `.php` e `.css` independente.

## 🛠 Tecnologias

- **PHP** (PDO, sessões, password_hash com bcrypt)
- **MySQL** (script SQL incluso para criação do banco)
- **HTML5** + **CSS3** (cada módulo com folha de estilo separada)
- JavaScript vanilla para auxílio na interface do administrador

## 📁 Estrutura do projeto
