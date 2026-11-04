<?php
require_once __DIR__ . '/conexao.php';
exigir_login();

$id  = (int)($_GET['id'] ?? 0);
$uid = (int)usuario_logado()['id'];

$consulta = $pdo->prepare(SQL_LIVRO . " WHERE l.id = :id");
$consulta->execute([':id' => $id]);
$livro = $consulta->fetch();
if (!$livro) {
    flash('Livro não encontrado.', 'erro');
    redirecionar('index.php');
}

// ---------- Ações: avaliar / remover avaliação ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = campo('acao');

    if ($acao === 'avaliar') {
        $nota       = (int)campo('nota');
        $comentario = mb_substr(campo('comentario'), 0, 500);
        if ($nota < 1 || $nota > 5) {
            flash('Escolha uma nota de 1 a 5 estrelas.', 'erro');
        } else {
            $c = $pdo->prepare(
                "INSERT INTO avaliacoes (livro_id, usuario_id, nota, comentario)
                 VALUES (:livro, :usuario, :nota, :comentario)
                 ON DUPLICATE KEY UPDATE nota = VALUES(nota),
                                         comentario = VALUES(comentario),
                                         atualizado_em = NOW()"
            );
            $c->execute([
                ':livro'      => $id,
                ':usuario'    => $uid,
                ':nota'       => $nota,
                ':comentario' => $comentario !== '' ? $comentario : null,
            ]);
            flash('Sua avaliação foi salva. Obrigado!');
        }
    } elseif ($acao === 'remover') {
        // O autor remove a própria avaliação; o admin pode remover qualquer uma.
        $c = $pdo->prepare("DELETE FROM avaliacoes
                            WHERE id = :id AND livro_id = :livro AND (usuario_id = :u OR :adm = 1)");
        $c->execute([
            ':id'    => (int)campo('avaliacao_id'),
            ':livro' => $id,
            ':u'     => $uid,
            ':adm'   => eh_admin() ? 1 : 0,
        ]);
        flash('Avaliação removida.');
    }
    redirecionar('livro.php?id=' . $id);
}

// ---------- Avaliações ----------
$c = $pdo->prepare(
    "SELECT a.id, a.usuario_id, a.nota, a.comentario, a.atualizado_em, u.nome
     FROM avaliacoes a JOIN usuarios u ON u.id = a.usuario_id
     WHERE a.livro_id = :livro ORDER BY a.atualizado_em DESC"
);
$c->execute([':livro' => $id]);
$avaliacoes = $c->fetchAll();

$distribuicao = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$minha = null;
foreach ($avaliacoes as $a) {
    $distribuicao[(int)$a['nota']]++;
    if ((int)$a['usuario_id'] === $uid) $minha = $a;
}
$total = count($avaliacoes);
$disp  = max(0, (int)$livro['quantidade'] - (int)$livro['emprestados']);

// ---------- Mais do mesmo gênero ----------
$relacionados = [];
if ($livro['genero']) {
    $c = $pdo->prepare(SQL_LIVRO . " WHERE l.genero = :g AND l.id <> :id ORDER BY media DESC, l.titulo LIMIT 4");
    $c->execute([':g' => $livro['genero'], ':id' => $id]);
    $relacionados = $c->fetchAll();
}

// ---------- Leitura ----------
$total_caps = (int)$livro['capitulos'];
$prog = null;
if ($total_caps > 0) {
    $c = $pdo->prepare("SELECT capitulo FROM progresso WHERE usuario_id = :u AND livro_id = :l");
    $c->execute([':u' => $uid, ':l' => $id]);
    $prog = $c->fetchColumn();
}

$titulo_pagina = $livro['titulo'];
require_once __DIR__ . '/topo.php';
?>

<a class="voltar" href="index.php">← Voltar ao acervo</a>

<section class="detalhe">
  <?= capa($livro, 'capa-grande') ?>
  <div class="detalhe-info">
    <?php if ($livro['genero']): ?>
      <a class="chip" href="index.php?genero=<?= urlencode($livro['genero']) ?>"><?= e($livro['genero']) ?></a>
    <?php endif; ?>
    <h1><?= e($livro['titulo']) ?></h1>
    <p class="autor-grande">
      <?= e($livro['autor']) ?><?= $livro['ano'] ? ', ' . e($livro['ano']) : '' ?>
    </p>

    <div class="nota-linha nota-grande">
      <?php if ($total > 0): ?>
        <?= estrelas($livro['media']) ?>
        <strong><?= number_format((float)$livro['media'], 1, ',', '.') ?></strong>
        <small>(<?= $total ?> avaliaç<?= $total === 1 ? 'ão' : 'ões' ?>)</small>
      <?php else: ?>
        <small class="mudo">Ainda sem avaliações. Seja o primeiro!</small>
      <?php endif; ?>
    </div>

    <div class="sinopse">
      <h2>Sobre o livro</h2>
      <?php if (trim((string)$livro['descricao']) !== ''): ?>
        <p><?= nl2br(e($livro['descricao'])) ?></p>
      <?php else: ?>
        <p class="mudo">Este livro ainda não tem descrição.</p>
      <?php endif; ?>
    </div>

    <?php if (trim((string)$livro['resumo']) !== ''): ?>
      <details class="resumo-box">
        <summary>Resumo da história <small>(contém spoilers)</small></summary>
        <p><?= nl2br(e($livro['resumo'])) ?></p>
      </details>
    <?php endif; ?>

    <?php if ($total_caps > 0 || eh_admin()): ?>
      <div class="acoes leitura-acoes">
        <?php if ($total_caps > 0): ?>
          <a class="btn btn-primario" href="ler.php?id=<?= (int)$livro['id'] ?>"><?= $prog ? 'Continuar leitura' : 'Ler agora' ?></a>
          <?php if ($prog): ?><a class="btn btn-fantasma" href="ler.php?id=<?= (int)$livro['id'] ?>&cap=1">Recomeçar</a><?php endif; ?>
          <small class="mudo"><?= $total_caps ?> capítulo<?= $total_caps === 1 ? '' : 's' ?></small>
        <?php endif; ?>
        <?php if (eh_admin()): ?>
          <a class="btn btn-sm btn-fantasma" href="conteudo.php?id=<?= (int)$livro['id'] ?>"><?= $total_caps > 0 ? 'Gerenciar texto' : 'Adicionar texto para leitura' ?></a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="acoes">
      <span class="status <?= $disp > 0 ? 'status-ok' : 'status-off' ?>">
        <?= $disp > 0 ? $disp . ' de ' . (int)$livro['quantidade'] . ' exemplares disponíveis' : 'Todos os exemplares estão emprestados' ?>
      </span>
      <?php if (eh_admin()): ?>
        <a class="btn btn-sm btn-fantasma" href="editar.php?id=<?= (int)$livro['id'] ?>">Editar livro</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="duas-colunas">
  <div class="card" id="avaliar">
    <h2><?= $minha ? 'Sua avaliação' : 'Avaliar este livro' ?></h2>
    <form method="post" class="form">
      <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="avaliar">
      <div class="nota-input" role="radiogroup" aria-label="Nota">
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <input type="radio" id="n<?= $i ?>" name="nota" value="<?= $i ?>" <?= $minha && (int)$minha['nota'] === $i ? 'checked' : '' ?> required>
          <label for="n<?= $i ?>" title="<?= $i ?> estrela(s)">★</label>
        <?php endfor; ?>
      </div>
      <label>Comentário <small class="dica">opcional, até 500 caracteres</small>
        <textarea name="comentario" rows="3" maxlength="500" placeholder="O que você achou deste livro?"><?= e($minha['comentario'] ?? '') ?></textarea>
      </label>
      <div><button class="btn btn-primario"><?= $minha ? 'Atualizar avaliação' : 'Enviar avaliação' ?></button></div>
    </form>
  </div>

  <div class="card">
    <h2>Distribuição das notas</h2>
    <?php foreach ($distribuicao as $estrela => $qtd): $pct = $total ? round($qtd / $total * 100) : 0; ?>
      <div class="barra-linha">
        <span><?= $estrela ?> ★</span>
        <div class="barra"><i style="width:<?= $pct ?>%"></i></div>
        <small><?= $qtd ?></small>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<h2 class="titulo-secao">Avaliações dos leitores (<?= $total ?>)</h2>
<?php if (!$avaliacoes): ?>
  <div class="vazio">Nenhuma avaliação ainda.</div>
<?php else: ?>
  <div class="lista-av">
    <?php foreach ($avaliacoes as $a): ?>
      <article class="av">
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($a['nome'], 0, 1))) ?></span>
        <div class="av-corpo">
          <div class="av-topo">
            <strong><?= e($a['nome']) ?></strong>
            <?= estrelas($a['nota']) ?>
            <small class="mudo"><?= data_br($a['atualizado_em']) ?></small>
          </div>
          <?php if ($a['comentario']): ?><p><?= nl2br(e($a['comentario'])) ?></p><?php endif; ?>
          <?php if ((int)$a['usuario_id'] === $uid || eh_admin()): ?>
            <form method="post" onsubmit="return confirm('Remover esta avaliação?')">
              <?= csrf_campo() ?>
              <input type="hidden" name="acao" value="remover">
              <input type="hidden" name="avaliacao_id" value="<?= (int)$a['id'] ?>">
              <button class="btn btn-sm btn-perigo">Remover</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($relacionados): ?>
<section class="prateleira-bloco">
  <div class="titulo-secao-linha"><h2>Mais em <?= e($livro['genero']) ?></h2></div>
  <div class="prateleira"><?php foreach ($relacionados as $l) echo cartao_livro($l); ?></div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/rodape.php'; ?>
