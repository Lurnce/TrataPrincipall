<?php
require_once __DIR__ . '/conexao.php';
exigir_admin();

$erros      = [];
$nome       = '';
$turma      = '';
$usuario_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = campo('acao');

    if ($acao === 'excluir') {
        try {
            $c = $pdo->prepare("DELETE FROM alunos WHERE id = :id");
            $c->execute([':id' => (int)campo('aluno_id')]);
            flash('Aluno excluído.');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                flash('Este aluno tem empréstimos registrados e não pode ser excluído.', 'erro');
            } else {
                error_log('Trata: erro ao excluir aluno - ' . $ex->getMessage());
                flash('Não foi possível excluir o aluno.', 'erro');
            }
        }
        redirecionar('alunos.php');
    }

    // ---------- Novo aluno ----------
    $nome       = campo('nome');
    $turma      = campo('turma');
    $usuario_id = (int)campo('usuario_id');
    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 100) $erros[] = 'Informe o nome do aluno (2 a 100 caracteres).';
    if ($turma === '' || mb_strlen($turma) > 20)         $erros[] = 'Informe a turma (até 20 caracteres).';

    if (!$erros) {
        try {
            $c = $pdo->prepare("INSERT INTO alunos (nome, turma, usuario_id) VALUES (:nome, :turma, :u)");
            $c->execute([':nome' => $nome, ':turma' => $turma, ':u' => $usuario_id > 0 ? $usuario_id : null]);
            flash('Aluno cadastrado.');
            redirecionar('alunos.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $erros[] = 'Essa conta de leitor já está vinculada a outro aluno.';
            } else {
                error_log('Trata: erro ao cadastrar aluno - ' . $ex->getMessage());
                $erros[] = 'Não foi possível cadastrar o aluno.';
            }
        }
    }
}

$alunos = $pdo->query(
    "SELECT a.id, a.nome, a.turma, u.email,
            (SELECT COUNT(*) FROM emprestimos e WHERE e.aluno_id = a.id AND e.data_devolucao IS NULL) AS abertos,
            (SELECT COUNT(*) FROM emprestimos e WHERE e.aluno_id = a.id) AS total
     FROM alunos a LEFT JOIN usuarios u ON u.id = a.usuario_id
     ORDER BY a.nome"
)->fetchAll();

// Contas de leitor ainda não vinculadas a nenhum aluno
$contas_livres = $pdo->query(
    "SELECT u.id, u.nome, u.email FROM usuarios u
     WHERE u.perfil = 'leitor' AND NOT EXISTS (SELECT 1 FROM alunos a WHERE a.usuario_id = u.id)
     ORDER BY u.nome"
)->fetchAll();

$titulo_pagina = 'Alunos';
require_once __DIR__ . '/topo.php';
?>

<div class="page-head">
  <div>
    <h1>Alunos</h1>
    <p class="sub">Cadastre os alunos que podem retirar livros.</p>
  </div>
</div>

<?php if ($erros): ?>
  <div class="alerta alerta-erro"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card">
  <h2>Novo aluno</h2>
  <form method="post" class="linha">
    <?= csrf_campo() ?>
    <input name="nome" value="<?= e($nome) ?>" placeholder="Nome completo" required maxlength="100" aria-label="Nome">
    <input name="turma" value="<?= e($turma) ?>" placeholder="Turma (ex.: 7º A)" required maxlength="20" aria-label="Turma">
    <select name="usuario_id" aria-label="Conta de leitor">
      <option value="">Sem conta vinculada</option>
      <?php foreach ($contas_livres as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $usuario_id === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?> (<?= e($c['email']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primario">Cadastrar</button>
  </form>
  <p class="mudo dica-linha">Vincular uma conta permite que o leitor veja seus empréstimos no perfil e receba recomendações melhores.</p>
</div>

<h2 class="titulo-secao">Alunos cadastrados (<?= count($alunos) ?>)</h2>
<?php if (!$alunos): ?>
  <div class="vazio">Nenhum aluno cadastrado.</div>
<?php else: ?>
<div class="tabela-wrap">
  <table>
    <thead>
      <tr><th>Nome</th><th>Turma</th><th>Conta</th><th>Em aberto</th><th>Total</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($alunos as $a): ?>
      <tr>
        <td><?= e($a['nome']) ?></td>
        <td><?= e($a['turma']) ?></td>
        <td><?= $a['email'] ? e($a['email']) : '<span class="mudo">Não vinculada</span>' ?></td>
        <td><?= (int)$a['abertos'] ?></td>
        <td><?= (int)$a['total'] ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Excluir este aluno?')">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="aluno_id" value="<?= (int)$a['id'] ?>">
            <button class="btn btn-sm btn-perigo">Excluir</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/rodape.php'; ?>
