<?php
require_once __DIR__ . '/conexao.php';
exigir_login();

$uid   = (int)usuario_logado()['id'];
$tipos = ['bug' => 'Bug / problema', 'sugestao' => 'Sugestão', 'duvida' => 'Dúvida'];
$erros = [];

$tipo = $_GET['tipo'] ?? 'bug';
if (!isset($tipos[$tipo])) $tipo = 'bug';
$titulo_relato = '';
$descricao     = '';
$pagina        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = campo('acao');

    if ($acao === 'relatar') {
        $tipo          = campo('tipo');
        $titulo_relato = campo('titulo');
        $descricao     = campo('descricao');
        $pagina        = campo('pagina');

        if (!isset($tipos[$tipo]))                                                 { $erros[] = 'Escolha o tipo do relato.'; $tipo = 'bug'; }
        if (mb_strlen($titulo_relato) < 3 || mb_strlen($titulo_relato) > 120)       $erros[] = 'Dê um título ao relato (3 a 120 caracteres).';
        if (mb_strlen($descricao) < 10 || mb_strlen($descricao) > 2000)             $erros[] = 'Descreva com pelo menos 10 caracteres (até 2.000).';
        if (mb_strlen($pagina) > 200)                                               $erros[] = 'O campo da página pode ter até 200 caracteres.';

        if (!$erros) {
            try {
                $c = $pdo->prepare("INSERT INTO relatos (usuario_id, tipo, titulo, descricao, pagina)
                                    VALUES (:u, :t, :ti, :d, :p)");
                $c->execute([':u' => $uid, ':t' => $tipo, ':ti' => $titulo_relato, ':d' => $descricao,
                             ':p' => $pagina !== '' ? $pagina : null]);
                flash('Relato enviado. Obrigado por ajudar a melhorar o Trata!');
                redirecionar('suporte.php#meus');
            } catch (PDOException $ex) {
                error_log('Trata: erro ao salvar relato - ' . $ex->getMessage());
                $erros[] = 'Não foi possível enviar agora. Confirme se o atualizacao.sql foi executado.';
            }
        }
    } elseif ($acao === 'status' && eh_admin()) {
        $novo = campo('status') === 'resolvido' ? 'resolvido' : 'aberto';
        $c = $pdo->prepare("UPDATE relatos SET status = :s WHERE id = :id");
        $c->execute([':s' => $novo, ':id' => (int)campo('relato_id')]);
        flash($novo === 'resolvido' ? 'Relato marcado como resolvido.' : 'Relato reaberto.');
        redirecionar('suporte.php#todos');
    }
}

$meus = $todos = [];
try {
    $c = $pdo->prepare("SELECT id, tipo, titulo, descricao, status, criado_em FROM relatos
                        WHERE usuario_id = :u ORDER BY criado_em DESC LIMIT 20");
    $c->execute([':u' => $uid]);
    $meus = $c->fetchAll();

    if (eh_admin()) {
        $todos = $pdo->query("SELECT r.id, r.tipo, r.titulo, r.descricao, r.pagina, r.status, r.criado_em, u.nome
                              FROM relatos r LEFT JOIN usuarios u ON u.id = r.usuario_id
                              ORDER BY r.status = 'resolvido', r.criado_em DESC LIMIT 100")->fetchAll();
    }
} catch (PDOException $ex) {
    $erros[] = 'A central de suporte precisa do atualizacao.sql no banco.';
}

$faq = [
    ['Como pego um livro emprestado?', 'Procure o bibliotecário. Ele registra o empréstimo no sistema, e o prazo padrão é de ' . PRAZO_DIAS . ' dias.'],
    ['Como funcionam as recomendações?', 'Usamos o gênero favorito do seu perfil, as notas 4 e 5 que você dá, os livros que você pegou emprestado e os que começou a ler.'],
    ['Por que alguns livros não têm o botão Ler?', 'Só livros com texto cadastrado podem ser lidos no site. O bibliotecário adiciona o texto, e apenas de obras de domínio público ou autorizadas.'],
    ['Como mudo o tema ou o tamanho da letra?', 'Em Configurações. O tema vale para todo o site, e o tamanho, a fonte e a largura valem para o leitor.'],
    ['Como altero minha senha?', 'Em Meu perfil, na seção Alterar senha. Você precisa informar a senha atual.'],
    ['Como relato um problema?', 'Use o formulário abaixo. Quanto mais detalhes (o que você fez e o que aconteceu), mais fácil de resolver.'],
];

$titulo_pagina = 'Ajuda e suporte';
require_once __DIR__ . '/topo.php';
?>

<div class="page-head">
  <div>
    <h1>Ajuda e suporte</h1>
    <p class="sub">Respostas rápidas e um canal para relatar problemas.</p>
  </div>
</div>

<?php if ($erros): ?>
  <div class="alerta alerta-erro"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="duas-colunas">
  <section class="card faq">
    <h2>Perguntas frequentes</h2>
    <?php foreach ($faq as [$p, $r]): ?>
      <details><summary><?= e($p) ?></summary><p><?= e($r) ?></p></details>
    <?php endforeach; ?>
  </section>

  <section class="card" id="relatar">
    <h2>Enviar relato</h2>
    <form method="post" class="form">
      <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="relatar">
      <label>Tipo
        <select name="tipo">
          <?php foreach ($tipos as $k => $rotulo): ?>
            <option value="<?= e($k) ?>" <?= $k === $tipo ? 'selected' : '' ?>><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Título
        <input name="titulo" value="<?= e($titulo_relato) ?>" required maxlength="120" placeholder="Resuma em uma frase">
      </label>
      <label>Descrição
        <textarea name="descricao" rows="5" required minlength="10" maxlength="2000" placeholder="O que aconteceu? O que você esperava?"><?= e($descricao) ?></textarea>
      </label>
      <label>Página <small class="dica">opcional, ex.: Empréstimos</small>
        <input name="pagina" value="<?= e($pagina) ?>" maxlength="200">
      </label>
      <div><button class="btn btn-primario">Enviar relato</button></div>
    </form>
  </section>
</div>

<h2 class="titulo-secao" id="meus">Meus relatos</h2>
<?php if (!$meus): ?>
  <div class="vazio">Você ainda não enviou nenhum relato.</div>
<?php else: ?>
  <div class="card">
    <?php foreach ($meus as $r): ?>
      <div class="relato">
        <div class="relato-topo">
          <strong><?= e($r['titulo']) ?></strong>
          <span class="chip"><?= e($tipos[$r['tipo']] ?? $r['tipo']) ?></span>
          <span class="badge <?= $r['status'] === 'resolvido' ? 'badge-ok' : 'badge-aviso' ?>"><?= $r['status'] === 'resolvido' ? 'Resolvido' : 'Em análise' ?></span>
          <small class="mudo"><?= data_br($r['criado_em']) ?></small>
        </div>
        <p><?= e($r['descricao']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (eh_admin()): ?>
  <h2 class="titulo-secao" id="todos">Todos os relatos (bibliotecário)</h2>
  <?php if (!$todos): ?>
    <div class="vazio">Nenhum relato recebido.</div>
  <?php else: ?>
    <div class="card">
      <?php foreach ($todos as $r): ?>
        <div class="relato">
          <div class="relato-topo">
            <strong><?= e($r['titulo']) ?></strong>
            <span class="chip"><?= e($tipos[$r['tipo']] ?? $r['tipo']) ?></span>
            <span class="badge <?= $r['status'] === 'resolvido' ? 'badge-ok' : 'badge-aviso' ?>"><?= $r['status'] === 'resolvido' ? 'Resolvido' : 'Aberto' ?></span>
            <small class="mudo"><?= e($r['nome'] ?? 'Conta removida') ?> · <?= data_br($r['criado_em']) ?><?= $r['pagina'] ? ' · ' . e($r['pagina']) : '' ?></small>
          </div>
          <p><?= e($r['descricao']) ?></p>
          <form method="post">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="status">
            <input type="hidden" name="relato_id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="status" value="<?= $r['status'] === 'resolvido' ? 'aberto' : 'resolvido' ?>">
            <button class="btn btn-sm btn-fantasma"><?= $r['status'] === 'resolvido' ? 'Reabrir' : 'Marcar como resolvido' ?></button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/rodape.php'; ?>
