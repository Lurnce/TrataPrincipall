<?php
require_once __DIR__ . '/conexao.php';
exigir_login();

$uid = (int)usuario_logado()['id'];
$id  = (int)($_GET['id'] ?? 0);

$c = $pdo->prepare("SELECT id, titulo, autor FROM livros WHERE id = :id");
$c->execute([':id' => $id]);
$livro = $c->fetch();
if (!$livro) {
    flash('Livro não encontrado.', 'erro');
    redirecionar('index.php');
}

$c = $pdo->prepare("SELECT numero, titulo FROM capitulos WHERE livro_id = :id ORDER BY numero");
$c->execute([':id' => $id]);
$caps = $c->fetchAll();
if (!$caps) {
    flash('Este livro ainda não tem texto para leitura.', 'erro');
    redirecionar('livro.php?id=' . $id);
}
$numeros = array_map('intval', array_column($caps, 'numero'));

// Capítulo pedido; sem pedido, continua de onde parou
$cap = (int)($_GET['cap'] ?? 0);
if (!in_array($cap, $numeros, true)) {
    $c = $pdo->prepare("SELECT capitulo FROM progresso WHERE usuario_id = :u AND livro_id = :l");
    $c->execute([':u' => $uid, ':l' => $id]);
    $salvo = (int)$c->fetchColumn();
    $cap = in_array($salvo, $numeros, true) ? $salvo : $numeros[0];
}

$c = $pdo->prepare("SELECT titulo, texto FROM capitulos WHERE livro_id = :l AND numero = :n");
$c->execute([':l' => $id, ':n' => $cap]);
$atual = $c->fetch();

// Salva o progresso
$c = $pdo->prepare("INSERT INTO progresso (usuario_id, livro_id, capitulo) VALUES (:u, :l, :n)
                    ON DUPLICATE KEY UPDATE capitulo = VALUES(capitulo), atualizado_em = NOW()");
$c->execute([':u' => $uid, ':l' => $id, ':n' => $cap]);

$pos      = array_search($cap, $numeros, true);
$anterior = $numeros[$pos - 1] ?? null;
$proximo  = $numeros[$pos + 1] ?? null;
$paragrafos = preg_split('/\n{2,}/', str_replace(["\r\n", "\r"], "\n", (string)$atual['texto']));

$titulo_pagina = $livro['titulo'] . ' · Leitura';
require_once __DIR__ . '/topo.php';
?>

<a class="voltar" href="livro.php?id=<?= $id ?>">← Voltar ao livro</a>

<div class="leitor" data-leitor>
  <div class="leitor-barra">
    <select aria-label="Capítulo" onchange="location.href='ler.php?id=<?= $id ?>&cap=' + this.value">
      <?php foreach ($caps as $k): ?>
        <option value="<?= (int)$k['numero'] ?>" <?= (int)$k['numero'] === $cap ? 'selected' : '' ?>>
          <?= (int)$k['numero'] ?>. <?= e($k['titulo']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <div class="grupo-btn" aria-label="Tamanho da letra">
      <button type="button" data-lt="menos" aria-label="Diminuir letra">A−</button>
      <button type="button" data-lt="mais" aria-label="Aumentar letra">A+</button>
    </div>
    <div class="grupo-btn" aria-label="Fonte">
      <button type="button" data-lt="serif">Serifa</button>
      <button type="button" data-lt="sans">Sem serifa</button>
    </div>
    <div class="grupo-btn" aria-label="Largura do texto">
      <button type="button" data-lt="estreito">Estreito</button>
      <button type="button" data-lt="normal">Normal</button>
      <button type="button" data-lt="largo">Largo</button>
    </div>
  </div>

  <article class="leitor-texto">
    <p class="livro-nome"><?= e($livro['titulo']) ?> · <?= e($livro['autor']) ?></p>
    <h1><?= e($atual['titulo']) ?></h1>
    <?php foreach ($paragrafos as $p): if (trim($p) === '') continue; ?>
      <p><?= nl2br(e(trim($p))) ?></p>
    <?php endforeach; ?>
  </article>

  <nav class="leitor-nav" aria-label="Navegação entre capítulos">
    <?php if ($anterior !== null): ?>
      <a class="btn btn-fantasma" href="ler.php?id=<?= $id ?>&cap=<?= $anterior ?>">← Capítulo anterior</a>
    <?php else: ?><span></span><?php endif; ?>
    <?php if ($proximo !== null): ?>
      <a class="btn btn-primario" href="ler.php?id=<?= $id ?>&cap=<?= $proximo ?>">Próximo capítulo →</a>
    <?php else: ?><span></span><?php endif; ?>
  </nav>

  <?php if ($proximo === null): ?>
    <div class="leitor-fim">
      <p class="mudo">Você chegou ao fim. Gostou da leitura?</p>
      <a class="btn btn-fantasma" href="livro.php?id=<?= $id ?>#avaliar">Avaliar este livro</a>
    </div>
  <?php endif; ?>
</div>

<div class="leitura-progresso" aria-hidden="true"><i></i></div>
<script>
(function () {
  var barra = document.querySelector('.leitura-progresso i');
  function atualizar() {
    var h = document.documentElement;
    var total = h.scrollHeight - h.clientHeight;
    barra.style.width = (total > 0 ? Math.min(100, h.scrollTop / total * 100) : 100) + '%';
  }
  window.addEventListener('scroll', atualizar, { passive: true });
  window.addEventListener('resize', atualizar);
  atualizar();
})();
</script>
<?php require_once __DIR__ . '/rodape.php'; ?>
