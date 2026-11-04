<?php
require_once __DIR__ . '/conexao.php';
exigir_login();

$u   = usuario_logado();
$uid = (int)$u['id'];

// Escolha rápida do gênero favorito (cartão de boas-vindas das recomendações)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $g = campo('genero_favorito');
    if (campo('acao') === 'favorito' && in_array($g, lista_generos($pdo), true)) {
        $c = $pdo->prepare("UPDATE usuarios SET genero_favorito = :g WHERE id = :u");
        $c->execute([':g' => $g, ':u' => $uid]);
        flash('Pronto! Já separamos livros de ' . $g . ' para você.');
    }
    redirecionar('index.php');
}

$ordens = [
    'titulo'    => ['Título (A–Z)',            'l.titulo'],
    'avaliados' => ['Mais bem avaliados',      'media DESC, total_aval DESC, l.titulo'],
    'recentes'  => ['Adicionados recentemente', 'l.id DESC'],
    'ano'       => ['Ano de publicação',       'l.ano DESC, l.titulo'],
];

$busca  = trim($_GET['busca'] ?? '');
$genero = trim($_GET['genero'] ?? '');
$ordem  = $_GET['ordem'] ?? 'titulo';
if (!isset($ordens[$ordem])) $ordem = 'titulo';
$like   = '%' . addcslashes($busca, '%_\\') . '%';

$consulta = $pdo->prepare(SQL_LIVRO . "
        WHERE (l.titulo LIKE :busca OR l.autor LIKE :busca2)
          AND (:g1 = '' OR l.genero = :g2)
        ORDER BY " . $ordens[$ordem][1]);
$consulta->execute([':busca' => $like, ':busca2' => $like, ':g1' => $genero, ':g2' => $genero]);
$livros = $consulta->fetchAll();

$generos = lista_generos($pdo);
$stats   = $pdo->query("SELECT COUNT(*) AS titulos, COALESCE(SUM(quantidade), 0) AS exemplares FROM livros")->fetch();
$emprestados_agora = (int)$pdo->query("SELECT COUNT(*) FROM emprestimos WHERE data_devolucao IS NULL")->fetchColumn();

// Prateleiras em destaque: só na visão padrão (sem busca, filtro ou ordenação)
$filtrando   = $busca !== '' || $genero !== '';
$prateleiras = !$filtrando && $ordem === 'titulo';
$recs = $top = $lendo = [];
$sem_favorito = false;
if ($prateleiras) {
    $recs = recomendados($pdo, $uid, 4);
    $c = $pdo->prepare("SELECT genero_favorito FROM usuarios WHERE id = :u");
    $c->execute([':u' => $uid]);
    $sem_favorito = (string)$c->fetchColumn() === '';
    $c = $pdo->prepare(SQL_LIVRO . " JOIN progresso p ON p.livro_id = l.id AND p.usuario_id = :u
                                     ORDER BY p.atualizado_em DESC LIMIT 4");
    $c->execute([':u' => $uid]);
    $lendo = $c->fetchAll();
    $top  = $pdo->query("SELECT * FROM (" . SQL_LIVRO . ") t
                         WHERE total_aval > 0
                         ORDER BY media DESC, total_aval DESC, titulo LIMIT 4")->fetchAll();
}

function link_filtro(string $busca, string $genero, string $ordem): string {
    $q = array_filter(['busca' => $busca, 'genero' => $genero, 'ordem' => $ordem !== 'titulo' ? $ordem : ''],
                      fn($v) => $v !== '');
    return 'index.php' . ($q ? '?' . http_build_query($q) : '');
}

$titulo_pagina = 'Acervo';
require_once __DIR__ . '/topo.php';
?>

<section class="hero">
  <div class="hero-capas" aria-hidden="true">
    <?php foreach (array_slice($top ?: $livros, 0, 3) as $destaque) echo capa($destaque); ?>
  </div>
  <h1>Olá, <em><?= e(primeiro_nome($u['nome'])) ?></em>.<br>O que vamos ler agora?</h1>

  <form method="get" class="hero-busca" role="search">
    <input type="search" name="busca" value="<?= e($busca) ?>" placeholder="Buscar por título ou autor" aria-label="Buscar">
    <?php if ($genero !== ''): ?><input type="hidden" name="genero" value="<?= e($genero) ?>"><?php endif; ?>
    <?php if ($ordem !== 'titulo'): ?><input type="hidden" name="ordem" value="<?= e($ordem) ?>"><?php endif; ?>
    <button class="btn btn-ouro">Buscar</button>
  </form>

  <nav class="pills" aria-label="Gêneros">
    <a href="<?= e(link_filtro($busca, '', $ordem)) ?>" class="<?= $genero === '' ? 'ativo' : '' ?>">Todos</a>
    <?php foreach ($generos as $g): ?>
      <a href="<?= e(link_filtro($busca, $g, $ordem)) ?>" class="<?= $g === $genero ? 'ativo' : '' ?>"><?= e($g) ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="hero-nums">
    <span><b><?= (int)$stats['titulos'] ?></b> títulos</span>
    <span><b><?= (int)$stats['exemplares'] ?></b> exemplares</span>
    <span><b><?= $emprestados_agora ?></b> emprestados agora</span>
  </div>
</section>

<?php if ($lendo): ?>
<section class="prateleira-bloco">
  <div class="titulo-secao-linha"><h2>Continue lendo</h2></div>
  <div class="prateleira"><?php foreach ($lendo as $l) echo cartao_livro($l); ?></div>
</section>
<?php endif; ?>

<?php if (!$recs && $sem_favorito && $generos): ?>
<section class="card onboarding">
  <h2>Receba recomendações</h2>
  <p class="mudo">Escolha um gênero de que você gosta e montamos uma prateleira só sua. Ela melhora conforme você avalia e lê.</p>
  <form method="post" class="pills pills-claras">
    <?= csrf_campo() ?>
    <input type="hidden" name="acao" value="favorito">
    <?php foreach ($generos as $g): ?>
      <button name="genero_favorito" value="<?= e($g) ?>"><?= e($g) ?></button>
    <?php endforeach; ?>
  </form>
</section>
<?php endif; ?>

<?php if ($recs): ?>
<section class="prateleira-bloco">
  <div class="titulo-secao-linha">
    <div>
      <h2>Escolhidos para você</h2>
      <p class="sub">Com base no seu gênero favorito, nas suas notas e nos seus empréstimos.</p>
    </div>
    <a class="link-suave" href="perfil.php">Ajustar gostos</a>
  </div>
  <div class="prateleira"><?php foreach ($recs as $l) echo cartao_livro($l); ?></div>
</section>
<?php endif; ?>

<?php if ($top): ?>
<section class="prateleira-bloco">
  <div class="titulo-secao-linha"><h2>Mais bem avaliados</h2></div>
  <div class="prateleira"><?php foreach ($top as $l) echo cartao_livro($l); ?></div>
</section>
<?php endif; ?>

<section class="prateleira-bloco">
  <div class="titulo-secao-linha">
    <h2><?= $filtrando ? 'Resultados' : 'Todo o acervo' ?> (<?= count($livros) ?>)</h2>
    <form method="get" class="ordenar">
      <?php if ($busca !== ''): ?><input type="hidden" name="busca" value="<?= e($busca) ?>"><?php endif; ?>
      <?php if ($genero !== ''): ?><input type="hidden" name="genero" value="<?= e($genero) ?>"><?php endif; ?>
      <label>Ordenar por
        <select name="ordem" onchange="this.form.submit()">
          <?php foreach ($ordens as $chave => [$rotulo]): ?>
            <option value="<?= e($chave) ?>" <?= $chave === $ordem ? 'selected' : '' ?>><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <noscript><button class="btn btn-sm btn-fantasma">Aplicar</button></noscript>
    </form>
  </div>

  <?php if (!$livros): ?>
    <div class="vazio">
      Nenhum livro encontrado.
      <?php if ($filtrando): ?><a href="index.php">Limpar busca</a><?php endif; ?>
    </div>
  <?php else: ?>
    <div class="prateleira"><?php foreach ($livros as $l) echo cartao_livro($l); ?></div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/rodape.php'; ?>
