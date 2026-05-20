<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'coordenador') {
    header('Location: login.php');
    exit;
}

$message = '';
$coordenadorId = $_SESSION['user_id'];

// Processar avaliação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_plano'])) {
    $planoId = $_POST['plano_id'];
    $status = $_POST['status'];
    $comment = trim($_POST['comment'] ?? '');
    $stmt = $pdo->prepare("UPDATE planos_semanais SET status=?, coordinator_comment=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?");
    $stmt->execute([$status, $comment, $coordenadorId, $planoId]);
    $message = "Avaliação registrada.";
}

// Filtros
$professores = $pdo->query("SELECT id, nome FROM users WHERE role='professor' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY ano")->fetchAll(PDO::FETCH_ASSOC);
$disciplinas = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

$teacherId = $_GET['teacher'] ?? ($professores[0]['id'] ?? null);
$turmaId = $_GET['turma'] ?? ($turmas[0]['id'] ?? null);
$bimestre = $_GET['bimestre'] ?? 1;
$disciplinaId = $_GET['disciplina'] ?? ($disciplinas[0]['id'] ?? null);

// Listar planos
$planos = [];
if ($teacherId && $turmaId && $disciplinaId) {
    $stmt = $pdo->prepare("SELECT p.*, u.nome as teacher_nome, d.nome as disc_nome FROM planos_semanais p 
                            JOIN users u ON u.id = p.teacher_id 
                            JOIN disciplinas d ON d.id = p.disciplina_id 
                            WHERE p.teacher_id=? AND p.turma_id=? AND p.disciplina_id=? AND p.bimestre=? 
                            ORDER BY p.data");
    $stmt->execute([$teacherId, $turmaId, $disciplinaId, $bimestre]);
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Avaliação dos Planos - Coordenador</title>
    <link rel="stylesheet" href="coordenador.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Painel do Coordenador</h1>
            <nav>
                <a href="dashboard_planosemanal.php">Dashboard</a>
                <a href="logout.php">Sair</a>
            </nav>
        </header>
        <?php if ($message): ?>
            <p class="msg"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="GET" class="filtros">
            <label>Professor:
                <select name="teacher" onchange="this.form.submit()">
                    <?php foreach ($professores as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id']==$teacherId?'selected':'' ?>><?= htmlspecialchars($p['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
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
                        <option value="<?= $i ?>" <?= $i==$bimestre?'selected':'' ?>><?= $i ?>º</option>
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

        <?php if (!empty($planos)): ?>
            <h2>Planos de <?= htmlspecialchars($planos[0]['teacher_nome']) ?> – <?= htmlspecialchars($planos[0]['disc_nome']) ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Página</th>
                        <th>O Que</th>
                        <th>Como</th>
                        <th>Recursos</th>
                        <th>P/Casa</th>
                        <th>Status</th>
                        <th>Avaliação</th>
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
                            <span class="status-<?= $pl['status'] ?>">
                                <?= $pl['status'] === 'pendente' ? 'Pendente' : ($pl['status'] === 'aprovado' ? 'Aprovado' : 'Revisão') ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" class="review-form">
                                <input type="hidden" name="plano_id" value="<?= $pl['id'] ?>">
                                <select name="status">
                                    <option value="pendente" <?= $pl['status']=='pendente'?'selected':'' ?>>Pendente</option>
                                    <option value="aprovado" <?= $pl['status']=='aprovado'?'selected':'' ?>>Aprovado</option>
                                    <option value="revisao" <?= $pl['status']=='revisao'?'selected':'' ?>>Revisão</option>
                                </select>
                                <textarea name="comment" placeholder="Comentário (opcional)"><?= htmlspecialchars($pl['coordinator_comment'] ?? '') ?></textarea>
                                <button type="submit" name="review_plano">Salvar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum plano encontrado para os filtros selecionados.</p>
        <?php endif; ?>
    </div>
</body>
</html>