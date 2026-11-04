<?php
require_once __DIR__ . '/conexao.php';
exigir_login();

$tema_atual    = tema_atual();
$titulo_pagina = 'Configurações';
require_once __DIR__ . '/topo.php';
?>

<div class="page-head">
  <div>
    <h1>Configurações</h1>
    <p class="sub">Deixe o Trata do seu jeito. As escolhas de aparência valem para este aparelho.</p>
  </div>
</div>

<div class="config-grade">

  <section class="card">
    <h2>Aparência</h2>
    <p class="sub">Escolha o tema do site.</p>
    <div class="opcoes" role="radiogroup" aria-label="Tema">
      <?php foreach (['claro' => 'Claro', 'escuro' => 'Escuro', 'auto' => 'Automático'] as $valor => $rotulo): ?>
        <label class="opcao">
          <input type="radio" name="tema_pagina" data-tema-opcao value="<?= $valor ?>" <?= $tema_atual === $valor ? 'checked' : '' ?>>
          <span><?= e($rotulo) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <p class="mudo dica-linha">Automático segue o tema do seu aparelho.</p>
  </section>

  <section class="card">
    <h2>Idioma</h2>
    <p class="sub">Idioma da interface.</p>
    <select aria-label="Idioma" style="max-width: 280px">
      <option selected>Português (Brasil)</option>
      <option disabled>English (em breve)</option>
    </select>
  </section>

  <section class="card leitor" data-leitor>
    <h2>Leitura</h2>
    <p class="sub">Como os livros aparecem no leitor.</p>
    <div class="linha-config">
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
    <div class="leitor-previa">
      <div class="leitor-texto">
        <p>Assim ficará o texto dos livros: um parágrafo de exemplo para você ajustar o tamanho, a fonte e a largura até ficar confortável.</p>
      </div>
    </div>
  </section>

  <section class="card">
    <h2>Conta</h2>
    <p class="sub">Dados do perfil, gênero favorito e senha.</p>
    <div class="acoes">
      <a class="btn btn-fantasma" href="perfil.php">Editar perfil</a>
      <form method="post" action="sair.php">
        <?= csrf_campo() ?>
        <button class="btn btn-perigo">Sair da conta</button>
      </form>
    </div>
  </section>

  <section class="card">
    <h2>Ajuda e suporte</h2>
    <p class="sub">Dúvidas, problemas e sugestões.</p>
    <div class="acoes">
      <a class="btn btn-fantasma" href="suporte.php">Perguntas frequentes</a>
      <a class="btn btn-fantasma" href="suporte.php?tipo=bug#relatar">Relatar um bug</a>
      <a class="btn btn-fantasma" href="suporte.php?tipo=sugestao#relatar">Enviar sugestão</a>
    </div>
  </section>

  <p class="mudo" style="text-align:center">Trata <?= e(VERSAO_APP) ?> · Biblioteca escolar</p>
</div>

<?php require_once __DIR__ . '/rodape.php'; ?>
