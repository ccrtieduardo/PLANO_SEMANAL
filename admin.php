<?php
require_once 'config.php';

// Acesso restrito ao admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Processamento de ações
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Criar / atualizar usuário
    if (isset($_POST['save_user'])) {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $role = $_POST['role'] ?? '';
        $disciplinas = $_POST['disciplinas'] ?? [];
        $turmas = $_POST['turmas'] ?? [];

        if ($nome && $email && $role) {
            // Se for edição (id enviado), atualiza; senão, insere
            if (!empty($_POST['user_id'])) {
                $userId = (int)$_POST['user_id'];
                // Atualiza dados básicos
                if (!empty($senha)) {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET nome=?, email=?, senha=?, role=? WHERE id=?");
                    $stmt->execute([$nome, $email, $hash, $role, $userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET nome=?, email=?, role=? WHERE id=?");
                    $stmt->execute([$nome, $email, $role, $userId]);
                }
                // Remove vínculos antigos e insere novos
                $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id=?")->execute([$userId]);
                $pdo->prepare("DELETE FROM teacher_classes WHERE teacher_id=?")->execute([$userId]);
            } else {
                // Novo usuário
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (nome, email, senha, role) VALUES (?,?,?,?)");
                $stmt->execute([$nome, $email, $hash, $role]);
                $userId = $pdo->lastInsertId();
            }

            // Insere disciplinas e turmas (apenas para professores)
            if ($role === 'professor') {
                if (!empty($disciplinas)) {
                    $stmtD = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, disciplina_id) VALUES (?,?)");
                    foreach ($disciplinas as $did) {
                        $stmtD->execute([$userId, (int)$did]);
                    }
                }
                if (!empty($turmas)) {
                    $stmtT = $pdo->prepare("INSERT INTO teacher_classes (teacher_id, turma_id) VALUES (?,?)");
                    foreach ($turmas as $tid) {
                        $stmtT->execute([$userId, (int)$tid]);
                    }
                }
            }
            $message = "Usuário salvo com sucesso!";
        } else {
            $message = "Preencha todos os campos obrigatórios.";
        }
    }

    // Excluir usuário
    if (isset($_POST['delete_user'])) {
        $userId = (int)$_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND role != 'admin'"); // protege admin
        $stmt->execute([$userId]);
        $message = "Usuário removido.";
    }
}

// Lista de usuários
$users = $pdo->query("SELECT id, nome, email, role FROM users ORDER BY role, nome")->fetchAll(PDO::FETCH_ASSOC);

// Para os selects de disciplinas e turmas
$allDisciplinas = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$allTurmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY ano")->fetchAll(PDO::FETCH_ASSOC);

// Se editar, buscar dados do usuário e vínculos
$editUser = null;
$editDisciplinas = [];
$editTurmas = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT id, nome, email, role FROM users WHERE id=?");
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editUser && $editUser['role'] === 'professor') {
        $editDisciplinas = $pdo->prepare("SELECT disciplina_id FROM teacher_subjects WHERE teacher_id=?");
        $editDisciplinas->execute([$editId]);
        $editDisciplinas = $editDisciplinas->fetchAll(PDO::FETCH_COLUMN);
        $editTurmas = $pdo->prepare("SELECT turma_id FROM teacher_classes WHERE teacher_id=?");
        $editTurmas->execute([$editId]);
        $editTurmas = $editTurmas->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Administração - Plano Semanal</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Painel do Administrador</h1>
            <nav>
                <a href="dashboard_planosemanal.php">Ver Planos</a>
                <a href="logout.php">Sair</a>
            </nav>
        </header>
        <?php if ($message): ?>
            <p class="msg"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <h2><?= $editUser ? 'Editar Usuário' : 'Cadastrar Usuário' ?></h2>
        <form method="POST" class="user-form">
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?? '' ?>">
            <div class="form-group">
                <label>Nome:</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($editUser['nome'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>E-mail:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Senha <?= $editUser ? '(deixe em branco para manter)' : '' ?>:</label>
                <input type="password" name="senha" <?= $editUser ? '' : 'required' ?>>
            </div>
            <div class="form-group">
                <label>Perfil:</label>
                <select name="role" id="roleSelect" required>
                    <option value="">Selecione...</option>
                    <option value="admin" <?= (isset($editUser['role']) && $editUser['role']==='admin')?'selected':'' ?>>Administrador</option>
                    <option value="professor" <?= (isset($editUser['role']) && $editUser['role']==='professor')?'selected':'' ?>>Professor</option>
                    <option value="coordenador" <?= (isset($editUser['role']) && $editUser['role']==='coordenador')?'selected':'' ?>>Coordenador</option>
                </select>
            </div>
            <div id="teacherFields" style="display: none;">
                <fieldset>
                    <legend>Disciplinas que leciona</legend>
                    <div class="checkbox-grid">
                        <?php foreach ($allDisciplinas as $d): ?>
                            <label>
                                <input type="checkbox" name="disciplinas[]" value="<?= $d['id'] ?>"
                                    <?= (in_array($d['id'], $editDisciplinas)) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($d['nome']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Turmas que atua</legend>
                    <div class="checkbox-grid">
                        <?php foreach ($allTurmas as $t): ?>
                            <label>
                                <input type="checkbox" name="turmas[]" value="<?= $t['id'] ?>"
                                    <?= (in_array($t['id'], $editTurmas)) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($t['nome']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            </div>
            <button type="submit" name="save_user">Salvar</button>
            <?php if ($editUser): ?>
                <a href="admin.php" class="cancel">Cancelar</a>
            <?php endif; ?>
        </form>

        <h2>Usuários Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nome']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                    <td>
                        <a href="admin.php?edit=<?= $u['id'] ?>">Editar</a>
                        <?php if ($u['role'] !== 'admin'): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Excluir usuário?')">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" name="delete_user" class="btn-delete">Excluir</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        const roleSelect = document.getElementById('roleSelect');
        const teacherFields = document.getElementById('teacherFields');
        function toggleTeacherFields() {
            teacherFields.style.display = roleSelect.value === 'professor' ? 'block' : 'none';
        }
        roleSelect.addEventListener('change', toggleTeacherFields);
        // Ao carregar a página, exibe se já estiver como professor
        toggleTeacherFields();
    </script>
</body>
</html>