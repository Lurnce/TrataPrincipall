<?php
$u             = usuario_logado();
$arquivo       = basename($_SERVER['SCRIPT_NAME']);
$titulo_pagina = $titulo_pagina ?? 'Acervo';
$pagina_auth   = $pagina_auth ?? false;
$tema          = tema_atual();

$menu = [['Acervo', 'index.php', ['index.php', 'livro.php', 'ler.php']]];
if (eh_admin()) {
    $menu[] = ['Novo livro',   'novo.php',        ['novo.php', 'editar.php', 'conteudo.php']];
    $menu[] = ['Empréstimos',  'emprestimos.php', ['emprestimos.php']];
    $menu[] = ['Alunos',       'alunos.php',      ['alunos.php']];
}

// Relatos de suporte em aberto (só para o bibliotecário)
$relatos_abertos = 0;
if (!$pagina_auth && eh_admin()) {
    try {
        $relatos_abertos = (int)$pdo->query("SELECT COUNT(*) FROM relatos WHERE status = 'aberto'")->fetchColumn();
    } catch (PDOException $ex) { /* tabela ainda não criada */ }
}
$flash = pegar_flash();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="<?= e($tema) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo_pagina) ?> · Trata</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="estilo.css?v=<?= (int)@filemtime(__DIR__ . '/estilo.css') ?>">
</head>
<body class="<?= $pagina_auth ? 'auth' : '' ?>">
<?php if (!$pagina_auth): ?>
<header class="topo">
  <div class="topo-in">
    <a class="marca" href="index.php">Trata</a>
    <nav class="menu" aria-label="Principal">
      <?php foreach ($menu as [$rotulo, $href, $arquivos]): ?>
        <a href="<?= $href ?>" class="<?= in_array($arquivo, $arquivos, true) ? 'ativo' : '' ?>"><?= e($rotulo) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php if ($u): ?>
    <a class="btn-icone" href="configuracoes.php" data-abrir-config aria-controls="painel-config" aria-expanded="false" title="Configurações" aria-label="Configurações">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/></svg>
    </a>
    <details class="usuario-menu">
      <summary>
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($u['nome'], 0, 1))) ?></span>
        <span class="usuario-nome"><?= e(primeiro_nome($u['nome'])) ?></span>
      </summary>
      <div class="usuario-painel">
        <div class="usuario-cab">
          <strong><?= e($u['nome']) ?></strong>
          <?php if (!empty($u['email'])): ?><small><?= e($u['email']) ?></small><?php endif; ?>
          <span class="chip"><?= $u['perfil'] === 'admin' ? 'Bibliotecário' : 'Leitor' ?></span>
        </div>
        <a href="perfil.php" class="<?= $arquivo === 'perfil.php' ? 'ativo' : '' ?>">Meu perfil</a>
        <a href="configuracoes.php" class="<?= $arquivo === 'configuracoes.php' ? 'ativo' : '' ?>">Configurações</a>
        <a href="suporte.php" class="<?= $arquivo === 'suporte.php' ? 'ativo' : '' ?>">Ajuda e suporte<?= $relatos_abertos ? ' <span class="contador">' . $relatos_abertos . '</span>' : '' ?></a>
        <form method="post" action="sair.php">
          <?= csrf_campo() ?>
          <button type="submit">Sair da conta</button>
        </form>
      </div>
    </details>
    <?php endif; ?>
  </div>
</header>

<?php if ($u): ?>
<div class="painel-fundo" data-fechar-config></div>
<aside class="painel-config" id="painel-config" role="dialog" aria-modal="true" aria-label="Configurações" aria-hidden="true" inert>
  <div class="painel-cab">
    <h2>Configurações</h2>
    <button type="button" class="painel-fechar" data-fechar-config aria-label="Fechar configurações">&times;</button>
  </div>
  <div class="painel-corpo">

    <section class="painel-sec">
      <h3>Aparência</h3>
      <div class="segmentado" role="radiogroup" aria-label="Tema">
        <label><input type="radio" name="tema" data-tema-opcao value="claro" <?= $tema === 'claro' ? 'checked' : '' ?>>
          <span><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>Claro</span></label>
        <label><input type="radio" name="tema" data-tema-opcao value="escuro" <?= $tema === 'escuro' ? 'checked' : '' ?>>
          <span><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/></svg>Escuro</span></label>
        <label><input type="radio" name="tema" data-tema-opcao value="auto" <?= $tema === 'auto' ? 'checked' : '' ?>>
          <span><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 3v18" /><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/></svg>Auto</span></label>
      </div>
    </section>

    <section class="painel-sec">
      <h3>Leitura</h3>
      <div class="linha-config">
        <div class="grupo-btn" aria-label="Tamanho da letra">
          <button type="button" data-lt="menos" aria-label="Diminuir letra">A−</button>
          <button type="button" data-lt="mais" aria-label="Aumentar letra">A+</button>
        </div>
        <div class="grupo-btn" aria-label="Fonte">
          <button type="button" data-lt="serif">Serifa</button>
          <button type="button" data-lt="sans">Sem serifa</button>
        </div>
      </div>
      <div class="linha-config" style="margin-top:12px">
        <div class="grupo-btn" aria-label="Largura do texto">
          <button type="button" data-lt="estreito">Estreito</button>
          <button type="button" data-lt="normal">Normal</button>
          <button type="button" data-lt="largo">Largo</button>
        </div>
      </div>
    </section>

    <section class="painel-sec">
      <h3>Idioma</h3>
      <select aria-label="Idioma">
        <option selected>Português (Brasil)</option>
        <option disabled>English (em breve)</option>
      </select>
    </section>

    <section class="painel-sec">
      <h3>Ajuda</h3>
      <nav class="painel-links" aria-label="Ajuda">
        <a href="suporte.php">Central de ajuda</a>
        <a href="suporte.php?tipo=bug#relatar">Relatar um bug</a>
        <a href="suporte.php?tipo=sugestao#relatar">Enviar sugestão</a>
        <a href="perfil.php">Meu perfil</a>
        <a href="configuracoes.php">Todas as configurações</a>
      </nav>
    </section>

    <section class="painel-sec painel-pe">
      <span>Trata <?= e(VERSAO_APP) ?></span>
      <form method="post" action="sair.php">
        <?= csrf_campo() ?>
        <button class="btn btn-sm btn-perigo">Sair da conta</button>
      </form>
    </section>

  </div>
</aside>
<?php endif; ?>
<main class="container">
<?php else: ?>
<main class="auth-pagina">
<?php endif; ?>

<?php if ($flash && !$pagina_auth): ?>
  <div class="alerta alerta-<?= $flash['tipo'] === 'erro' ? 'erro' : 'ok' ?>" role="status"><?= e($flash['msg']) ?></div>
<?php endif; ?>
