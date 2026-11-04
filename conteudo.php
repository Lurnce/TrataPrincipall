<?php
require_once __DIR__ . '/conexao.php';
exigir_admin();

$id = (int)($_GET['id'] ?? 0);
$c = $pdo->prepare("SELECT id, titulo, autor FROM livros WHERE id = :id");
$c->execute([':id' => $id]);
$livro = $c->fetch();
if (!$livro) {
    flash('Livro não encontrado.', 'erro');
    redirecionar('index.php');
}

$erros = [];
$texto = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = campo('acao');

    if ($acao === 'remover') {
        $c = $pdo->prepare("DELETE FROM capitulos WHERE livro_id = :id");
        $c->execute([':id' => $id]);
        flash('Texto removido. O livro deixa de ter o botão de leitura.');
        redirecionar('livro.php?id=' . $id);
    }

    if ($acao === 'salvar') {
        $texto = campo('texto', false);

        // Arquivo .txt (tem prioridade sobre o texto colado)
        $arq = $_FILES['arquivo'] ?? null;
        if ($arq && ($arq['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($arq['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['txt', 'md'], true))  $erros[] = 'Envie um arquivo .txt (ou .md).';
            elseif ($arq['size'] > 5 * 1024 * 1024)    $erros[] = 'O arquivo pode ter até 5 MB.';
            else {
                $conteudo = (string)file_get_contents($arq['tmp_name']);
                $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo); // remove BOM
                if (!mb_check_encoding($conteudo, 'UTF-8')) $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'ISO-8859-1');
                $texto = $conteudo;
            }
        } elseif ($arq && ($arq['error'] ?? 0) === UPLOAD_ERR_INI_SIZE) {
            $erros[] = 'O arquivo excede o limite do servidor (upload_max_filesize no php.ini).';
        }

        $caps = $erros ? [] : dividir_capitulos((string)$texto);
        if (!$erros && !$caps) $erros[] = 'Cole um texto ou envie um arquivo. Marque cada capítulo com uma linha "## Título".';

        if (!$erros) {
            try {
                $pdo->beginTransaction();
                $c = $pdo->prepare("DELETE FROM capitulos WHERE livro_id = :id");
                $c->execute([':id' => $id]);
                $c = $pdo->prepare("INSERT INTO capitulos (livro_id, numero, titulo, texto) VALUES (:l, :n, :t, :x)");
                foreach ($caps as $i => $cap) {
                    $c->execute([':l' => $id, ':n' => $i + 1, ':t' => $cap['titulo'], ':x' => $cap['texto']]);
                }
                $pdo->commit();
                flash(count($caps) . ' capítulo(s) salvo(s). O livro já pode ser lido.');
                redirecionar('livro.php?id=' . $id);
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Trata: erro ao salvar texto - ' . $ex->getMessage());
                $erros[] = 'Não foi possível salvar o texto. Se for muito grande, aumente max_allowed_packet no MariaDB.';
            }
        }
    }
}

// Texto atual, para edição
$c = $pdo->prepare("SELECT numero, titulo, texto FROM capitulos WHERE livro_id = :id ORDER BY numero");
$c->execute([':id' => $id]);
$existentes = $c->fetchAll();
if ($texto === null) {
    $partes = [];
    foreach ($existentes as $k) $partes[] = '## ' . $k['titulo'] . "\n\n" . $k['texto'];
    $texto = implode("\n\n", $partes);
}

$titulo_pagina = 'Texto de ' . $livro['titulo'];
require_once __DIR__ . '/topo.php';
?>

<a class="voltar" href="livro.php?id=<?= $id ?>">← Voltar ao livro</a>

<div class="page-head">
  <div>
    <h1>Texto para leitura</h1>
    <p class="sub"><?= e($livro['titulo']) ?>, <?= e($livro['autor']) ?>. <?= count($existentes) ?> capítulo(s) cadastrado(s).</p>
  </div>
</div>

<?php if ($erros): ?>
  <div class="alerta alerta-erro"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="alerta alerta-ok">
  Publique apenas obras de domínio público ou com autorização de quem detém os direitos.
  Textos de domínio público podem ser encontrados no portal Domínio Público, no Projeto Gutenberg e na Wikisource.
</div>

<form method="post" enctype="multipart/form-data" class="card form" style="max-width: 820px">
  <?= csrf_campo() ?>
  <input type="hidden" name="acao" value="salvar">
  <label>Texto do livro
    <small class="dica">Comece cada capítulo com uma linha como <code>## Capítulo I</code>. Separe os parágrafos com uma linha em branco.</small>
    <textarea name="texto" rows="18" placeholder="## Capítulo I&#10;&#10;Era uma vez..."><?= e($texto) ?></textarea>
  </label>
  <label>Ou envie um arquivo .txt <small class="dica">até 5 MB, codificação UTF-8. Substitui o texto acima.</small>
    <input type="file" name="arquivo" accept=".txt,.md,text/plain">
  </label>
  <div class="form-acoes">
    <button class="btn btn-primario">Salvar texto</button>
    <a class="btn btn-fantasma" href="livro.php?id=<?= $id ?>">Cancelar</a>
  </div>
</form>

<?php if ($existentes): ?>
<div class="card zona-perigo">
  <div>
    <h2>Remover texto</h2>
    <p class="mudo">Apaga todos os capítulos deste livro. Os dados do livro e as avaliações continuam.</p>
  </div>
  <form method="post" onsubmit="return confirm('Remover todo o texto deste livro?')">
    <?= csrf_campo() ?>
    <input type="hidden" name="acao" value="remover">
    <button class="btn btn-perigo">Remover texto</button>
  </form>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/rodape.php'; ?>
