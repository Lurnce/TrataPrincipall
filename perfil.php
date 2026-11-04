<?php
require_once __DIR__ . '/conexao.php';
exigir_login();

$uid     = (int)usuario_logado()['id'];
$generos = lista_generos($pdo);
$erros_perfil = [];
$erros_senha  = [];

function carregar_usuario(PDO $pdo, int $uid): array {
    $c = $pdo->prepare("SELECT id, nome, email, perfil, criado_em, bio, genero_favorito FROM usuarios WHERE id = :id");
    $c->execute([':id' => $uid]);
    return $c->fetch() ?: [];
}

$usr = carregar_usuario($pdo, $uid);
if (!$usr) {
    // Conta removida: encerra a sessão
    $_SESSION = [];
    redirecionar('login.php');
}

$form = [
    'nome'            => $usr['nome'],
    'bio'             => (string)$usr['bio'],
    'genero_favorito' => (string)$usr['genero_favorito'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = campo('acao');

    if ($acao === 'perfil') {
        $form = [
            'nome'            => campo('nome'),
            'bio'             => campo('bio'),
            'genero_favorito' => campo('genero_favorito'),
        ];
        if (mb_strlen($form['nome']) < 2 || mb_strlen($form['nome']) > 100) $erros_perfil[] = 'Informe seu nome (2 a 100 caracteres).';
        if (mb_strlen($form['bio']) > 200)                                  $erros_perfil[] = 'A bio pode ter até 200 caracteres.';
        if ($form['genero_favorito'] !== '' && !in_array($form['genero_favorito'], $generos, true)) {
            $erros_perfil[] = 'Escolha um gênero da lista.';
        }

        if (!$erros_perfil) {
            $c = $pdo->prepare("UPDATE usuarios SET nome = :n, bio = :b, genero_favorito = :g WHERE id = :id");
            $c->execute([
                ':n'  => $form['nome'],
                ':b'  => $form['bio'] !== '' ? $form['bio'] : null,
                ':g'  => $form['genero_favorito'] !== '' ? $form['genero_favorito'] : null,
                ':id' => $uid,
            ]);
            $_SESSION['usuario']['nome'] = $form['nome'];
            flash('Perfil atualizado.');
            redirecionar('perfil.php');
        }
    } elseif ($acao === 'senha') {
        $atual = campo('senha_atual', false);
        $nova  = campo('senha_nova', false);
        $conf  = campo('senha_conf', false);

        $c = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE id = :id");
        $c->execute([':id' => $uid]);
        if (!password_verify($atual, (string)$c->fetchColumn())) $erros_senha[] = 'A senha atual está incorreta.';
        if (mb_strlen($nova) < 8)                                $erros_senha[] = 'A nova senha deve ter pelo menos 8 caracteres.';
        if ($nova !== $conf)                                     $erros_senha[] = 'As senhas novas não conferem.';

        if (!$erros_senha) {
            $c = $pdo->prepare("UPDATE usuarios SET senha_hash = :h WHERE id = :id");
            $c->execute([':h' => password_hash($nova, PASSWORD_DEFAULT), ':id' => $uid]);
            session_regenerate_id(true);
            flash('Senha alterada.');
            redirecionar('perfil.php');
        }
    }
}

// ---------- Minhas avaliações ----------
$c = $pdo->prepare("SELECT l.id, l.titulo, l.autor, a.nota, a.comentario, a.atualizado_em
                    FROM avaliacoes a JOIN livros l ON l.id = a.livro_id
                    WHERE a.usuario_id = :u ORDER BY a.atualizado_em DESC");
$c->execute([':u' => $uid]);
$minhas = $c->fetchAll();

// ---------- Meus empréstimos (via aluno vinculado à conta) ----------
$c = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE usuario_id = :u");
$c->execute([':u' => $uid]);
$vinculado = (int)$c->fetchColumn() > 0;

$dias = (int)PRAZO_DIAS;
$emprestimos = [];
if ($vinculado) {
    $c = $pdo->prepare("SELECT l.id, l.titulo, e.data_emprestimo, e.data_devolucao,
                               DATE_ADD(e.data_emprestimo, INTERVAL $dias DAY) AS prazo
                        FROM emprestimos e
                        JOIN alunos al ON al.id = e.aluno_id
                        JOIN livros l  ON l.id = e.livro_id
                        WHERE al.usuario_id = :u
                        ORDER BY e.data_devolucao IS NOT NULL, e.data_emprestimo DESC, e.id DESC");
    $c->execute([':u' => $uid]);
    $emprestimos = $c->fetchAll();
}
$abertos = count(array_filter($emprestimos, fn($x) => $x['data_devolucao'] === null));
$media_minha = $minhas ? array_sum(array_column($minhas, 'nota')) / count($minhas) : 0;
$hoje = date('Y-m-d');

$titulo_pagina = 'Meu perfil';
require_once __DIR__ . '/topo.php';
?>

<section class="perfil-topo">
  <span class="avatar avatar-grande"><?= e(mb_strtoupper(mb_substr($usr['nome'], 0, 1))) ?></span>
  <div>
    <h1><?= e($usr['nome']) ?></h1>
    <p class="sub">
      <?= e($usr['email']) ?><br>
      <?= $usr['perfil'] === 'admin' ? 'Bibliotecário' : 'Leitor' ?>, membro desde <?= data_br($usr['criado_em']) ?>
    </p>
    <?php if ($usr['bio']): ?><p class="bio"><?= e($usr['bio']) ?></p><?php endif; ?>
  </div>
</section>

<div class="stats">
  <div class="stat"><span><?= count($minhas) ?></span><small>Livros avaliados</small></div>
  <div class="stat"><span><?= $minhas ? number_format($media_minha, 1, ',', '.') : '-' ?></span><small>Sua nota média</small></div>
  <div class="stat"><span><?= $vinculado ? $abertos : '-' ?></span><small>Empréstimos em aberto</small></div>
</div>

<section class="duas-colunas">
  <div class="card">
    <h2>Dados do perfil</h2>
    <?php if ($erros_perfil): ?>
      <div class="alerta alerta-erro"><ul><?php foreach ($erros_perfil as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="form">
      <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="perfil">
      <label>Nome
        <input name="nome" value="<?= e($form['nome']) ?>" required maxlength="100" autocomplete="name">
      </label>
      <label>E-mail
        <input value="<?= e($usr['email']) ?>" disabled>
      </label>
      <label>Gênero favorito <small class="dica">usado nas recomendações</small>
        <select name="genero_favorito">
          <option value="">Nenhum</option>
          <?php foreach ($generos as $g): ?>
            <option value="<?= e($g) ?>" <?= $g === $form['genero_favorito'] ? 'selected' : '' ?>><?= e($g) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Bio <small class="dica">até 200 caracteres</small>
        <textarea name="bio" rows="3" maxlength="200" placeholder="Conte um pouco sobre o que você gosta de ler"><?= e($form['bio']) ?></textarea>
      </label>
      <div><button class="btn btn-primario">Salvar perfil</button></div>
    </form>
  </div>

  <div class="card">
    <h2>Alterar senha</h2>
    <?php if ($erros_senha): ?>
      <div class="alerta alerta-erro"><ul><?php foreach ($erros_senha as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="form">
      <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="senha">
      <label>Senha atual
        <input type="password" name="senha_atual" required autocomplete="current-password">
      </label>
      <label>Nova senha <small class="dica">mínimo de 8 caracteres</small>
        <input type="password" name="senha_nova" required minlength="8" autocomplete="new-password">
      </label>
      <label>Confirmar nova senha
        <input type="password" name="senha_conf" required minlength="8" autocomplete="new-password">
      </label>
      <div><button class="btn btn-fantasma">Alterar senha</button></div>
    </form>
  </div>
</section>

<h2 class="titulo-secao">Meus empréstimos</h2>
<?php if (!$vinculado): ?>
  <div class="vazio">Sua conta ainda não está ligada a um cadastro de aluno. Peça ao bibliotecário para vinculá-la.</div>
<?php elseif (!$emprestimos): ?>
  <div class="vazio">Você ainda não pegou nenhum livro emprestado.</div>
<?php else: ?>
  <div class="tabela-wrap">
    <table>
      <thead><tr><th>Livro</th><th>Retirado em</th><th>Prazo</th><th>Situação</th></tr></thead>
      <tbody>
        <?php foreach ($emprestimos as $emp):
          $aberto   = $emp['data_devolucao'] === null;
          $atrasado = $aberto && $emp['prazo'] < $hoje; ?>
        <tr>
          <td><a href="livro.php?id=<?= (int)$emp['id'] ?>"><?= e($emp['titulo']) ?></a></td>
          <td><?= data_br($emp['data_emprestimo']) ?></td>
          <td><?= data_br($emp['prazo']) ?></td>
          <td>
            <?php if ($aberto): ?>
              <span class="badge <?= $atrasado ? 'badge-erro' : 'badge-aviso' ?>"><?= $atrasado ? 'Atrasado' : 'Em andamento' ?></span>
            <?php else: ?>
              <span class="badge badge-ok">Devolvido em <?= data_br($emp['data_devolucao']) ?></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<h2 class="titulo-secao">Minhas avaliações</h2>
<?php if (!$minhas): ?>
  <div class="vazio">Você ainda não avaliou nenhum livro. <a href="index.php">Explorar o acervo</a></div>
<?php else: ?>
  <div class="lista-av">
    <?php foreach ($minhas as $a): ?>
      <article class="av">
        <div class="av-corpo">
          <div class="av-topo">
            <strong><a href="livro.php?id=<?= (int)$a['id'] ?>"><?= e($a['titulo']) ?></a></strong>
            <?= estrelas($a['nota']) ?>
            <small class="mudo"><?= data_br($a['atualizado_em']) ?></small>
          </div>
          <?php if ($a['comentario']): ?><p><?= nl2br(e($a['comentario'])) ?></p><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/rodape.php'; ?>
