<?php
require_once 'config.php';

// Público – qualquer pessoa pode ver
$turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY ano")->fetchAll(PDO::FETCH_ASSOC);
$disciplinas = $pdo->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

$turmaId = $_GET['turma'] ?? ($turmas[0]['id'] ?? null);
$bimestre = $_GET['bimestre'] ?? 1;
$disciplinaId = $_GET['disciplina'] ?? ''; // vazio = todas

// Consulta de planos
$sql = "SELECT p.data, p.pagina, p.o_que, p.como, p.recursos, p.p_casa,
               u.nome AS professor, d.nome AS disciplina, t.nome AS turma
        FROM planos_semanais p
        JOIN users u ON u.id = p.teacher_id
        JOIN disciplinas d ON d.id = p.disciplina_id
        JOIN turmas t ON t.id = p.turma_id
        WHERE p.turma_id = ? AND p.bimestre = ?";
$params = [$turmaId, $bimestre];
if ($disciplinaId) {
    $sql .= " AND p.disciplina_id = ?";
    $params[] = $disciplinaId;
}
$sql .= " ORDER BY p.data, d.nome, u.nome";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plano Semanal - Colégio Christo Rei</title>
    <link rel="stylesheet" href="dashboard_planosemanal.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Plano Semanal – Colégio Educacional Christo Rei</h1>
            <nav>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span>Bem-vindo, <?= htmlspecialchars($_SESSION['user_nome']) ?> (<?= $_SESSION['user_role'] ?>)</span>
                    <a href="logout.php">Sair</a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                <?php endif; ?>
            </nav>
        </header>

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
                    <option value="">Todas</option>
                    <?php foreach ($disciplinas as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $d['id']==$disciplinaId?'selected':'' ?>><?= htmlspecialchars($d['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <?php if (empty($planos)): ?>
            <p>Nenhum plano encontrado para esta seleção.</p>
        <?php else: ?>
            <?php
            // Agrupar por disciplina e depois por professor
            $agrupado = [];
            foreach ($planos as $pl) {
                $disc = $pl['disciplina'];
                $prof = $pl['professor'];
                $agrupado[$disc][$prof][] = $pl;
            }
            ?>
            <?php foreach ($agrupado as $disciplina => $professores): ?>
                <div class="disciplina-bloco">
                    <h2><?= htmlspecialchars($disciplina) ?></h2>
                    <?php foreach ($professores as $professor => $itens): ?>
                        <h3>Professor: <?= htmlspecialchars($professor) ?></h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Página</th>
                                    <th>O Que</th>
                                    <th>Como</th>
                                    <th>Recursos</th>
                                    <th>P/ Casa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itens as $item): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($item['data'])) ?></td>
                                    <td><?= htmlspecialchars($item['pagina']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($item['o_que'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($item['como'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($item['recursos'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($item['p_casa'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>