<?php
require_once 'config.php';

// Restrito a professor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    header('Location: login.php');
    exit;
}

$teacherId = $_SESSION['user_id'];
$message = '';

// Obter turmas e disciplinas do professor
$turmas = $pdo->prepare("SELECT t.id, t.nome FROM turmas t JOIN teacher_classes tc ON t.id = tc.turma_id WHERE tc.teacher_id = ?");
$turmas->execute([$teacherId]);
$turmas = $turmas->fetchAll(PDO::FETCH_ASSOC);

$disciplinas = $pdo->prepare("SELECT d.id, d.nome FROM disciplinas d JOIN teacher_subjects ts ON d.id = ts.disciplina_id WHERE ts.teacher_id = ?");
$disciplinas->execute([$teacherId]);
$disciplinas = $disciplinas->fetchAll(PDO::FETCH_ASSOC);

// Filtros selecionados
$turmaId = $_GET['turma'] ?? ($turmas[0]['id'] ?? null);
$bimestre = $_GET['bimestre'] ?? 1;
$disciplinaId = $_GET['disciplina'] ?? ($disciplinas[0]['id'] ?? null);

// Processar inclusão/edição/exclusão de plano
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_plano'])) {
        $id = $_POST['plano_id'] ?? null;
        $data = $_POST['data'];
        $pagina = $_POST['pagina'];
        $o_que = $_POST['o_que'];
        $como = $_POST['como'];
        $recursos = $_POST['recursos'];
        $p_casa = $_POST['p_casa'];
        $turmaIdPost = $_POST['turma_id'];
        $disciplinaIdPost = $_POST['disciplina_id'];
        $bimestrePost = $_POST['bimestre'];

        if ($id) {
            $stmt = $pdo->prepare("UPDATE planos_semanais SET data=?, pagina=?, o_que=?, como=?, recursos=?, p_casa=? WHERE id=? AND teacher_id=?");
            $stmt->execute([$data, $pagina, $o_que, $como, $recursos, $p_casa, $id, $teacherId]);
            $message = "Plano atualizado.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO planos_semanais (teacher_id, turma_id, disciplina_id, bimestre, data, pagina, o_que, como, recursos, p_casa) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$teacherId, $turmaIdPost, $disciplinaIdPost, $bimestrePost, $data, $pagina, $o_que, $como, $recursos, $p_casa]);
            $message = "Plano adicionado.";
        }
    }
    if (isset($_POST['delete_plano'])) {
        $id = $_POST['plano_id'];
        $stmt = $pdo->prepare("DELETE FROM planos_semanais WHERE id=? AND teacher_id=?");
        $stmt->execute([$id, $teacherId]);
        $message = "Plano removido.";
    }
}

// Carregar planos do filtro
$planos = [];
if ($turmaId && $disciplinaId) {
    $stmt = $pdo->prepare("SELECT * FROM planos_semanais WHERE teacher_id=? AND turma_id=? AND disciplina_id=? AND bimestre=? ORDER BY data");
    $stmt->execute([$teacherId, $turmaId, $disciplinaId, $bimestre]);
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Dados para edição (se existir parâmetro edit)
$editPlano = null;
if (isset($_GET['edit_plano'])) {
    $editId = (int)$_GET['edit_plano'];
    $stmt = $pdo->prepare("SELECT * FROM planos_semanais WHERE id=? AND teacher_id=?");
    $stmt->execute([$editId, $teacherId]);
    $editPlano = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meus Planos - Professor</title>
    <link rel="stylesheet" href="professor.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Plano Semanal - Professor</h1>
            <nav>
                <a href="dashboard_planosemanal.php">Dashboard</a>
                <a href="logout.php">Sair</a>
            </nav>
        </header>
        <?php if ($message): ?>
            <p class="msg"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if (empty($turmas) || empty($disciplinas)): ?>
            <p>Você não está vinculado a nenhuma turma/disciplina. Contate o administrador.</p>
        <?php else: ?>
            <form method="GET" class="filtros">
                <label>Turma:
                    <select name="turma" onchange="this.form.submit()">
                        <?php foreach ($turmas as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $t['id']==$turmaId?'selected':'' ?>><?= htmlspecialchars($t['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Bimestre:
                    <select name="bimestre" onchange="this.form.submit()">
                        <?php for ($i=1; $i<=4; $i++): ?>
                            <option value="<?= $i ?>" <?= $i==$bimestre?'selected':'' ?>><?= $i ?>º bimestre</option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>Disciplina:
                    <select name="disciplina" onchange="this.form.submit()">
                        <?php foreach ($disciplinas as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $d['id']==$disciplinaId?'selected':'' ?>><?= htmlspecialchars($d['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>

            <h2>
                Planos – <?= htmlspecialchars($turmas[array_search($turmaId, array_column($turmas, 'id'))]['nome'] ?? '') ?> / 
                <?= htmlspecialchars($disciplinas[array_search($disciplinaId, array_column($disciplinas, 'id'))]['nome'] ?? '') ?> / 
                <?= $bimestre ?>º bimestre
            </h2>

            <!-- Formulário de adição/edição -->
            <div class="form-plano">
                <h3><?= $editPlano ? 'Editar registro' : 'Novo registro' ?></h3>
                <form method="POST">
                    <input type="hidden" name="plano_id" value="<?= $editPlano['id'] ?? '' ?>">
                    <input type="hidden" name="turma_id" value="<?= $turmaId ?>">
                    <input type="hidden" name="disciplina_id" value="<?= $disciplinaId ?>">
                    <input type="hidden" name="bimestre" value="<?= $bimestre ?>">
                    <div class="linha">
                        <label>Data: <input type="date" name="data" value="<?= $editPlano['data'] ?? '' ?>" required></label>
                        <label>Página: <input type="text" name="pagina" value="<?= htmlspecialchars($editPlano['pagina'] ?? '') ?>"></label>
                    </div>
                    <label>O Que: <textarea name="o_que" rows="2"><?= htmlspecialchars($editPlano['o_que'] ?? '') ?></textarea></label>
                    <label>Como: <textarea name="como" rows="2"><?= htmlspecialchars($editPlano['como'] ?? '') ?></textarea></label>
                    <label>Recursos: <textarea name="recursos" rows="2"><?= htmlspecialchars($editPlano['recursos'] ?? '') ?></textarea></label>
                    <label>P/ Casa: <textarea name="p_casa" rows="2"><?= htmlspecialchars($editPlano['p_casa'] ?? '') ?></textarea></label>
                    <button type="submit" name="save_plano">Salvar</button>
                    <?php if ($editPlano): ?>
                        <a href="professor.php?turma=<?= $turmaId ?>&bimestre=<?= $bimestre ?>&disciplina=<?= $disciplinaId ?>">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Página</th>
                        <th>O Que</th>
                        <th>Como</th>
                        <th>Recursos</th>
                        <th>P/ Casa</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($planos as $pl): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($pl['data'])) ?></td>
                        <td><?= htmlspecialchars($pl['pagina']) ?></td>
                        <td><?= nl2br(htmlspecialchars($pl['o_que'])) ?></td>
                        <td><?= nl2br(htmlspecialchars($pl['como'])) ?></td>
                        <td><?= nl2br(htmlspecialchars($pl['recursos'])) ?></td>
                        <td><?= nl2br(htmlspecialchars($pl['p_casa'])) ?></td>
                        <td>
                            <a href="professor.php?turma=<?= $turmaId ?>&bimestre=<?= $bimestre ?>&disciplina=<?= $disciplinaId ?>&edit_plano=<?= $pl['id'] ?>">Editar</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Excluir este registro?')">
                                <input type="hidden" name="plano_id" value="<?= $pl['id'] ?>">
                                <button type="submit" name="delete_plano" class="btn-delete">Excluir</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($planos)): ?>
                    <tr><td colspan="7">Nenhum plano cadastrado para este filtro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>