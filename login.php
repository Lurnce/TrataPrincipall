<?php
require_once __DIR__ . '/conexao.php';

if (usuario_logado()) redirecionar('index.php');

$erro  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $email = mb_strtolower(campo('email'));
    $senha = campo('senha', false);

    $consulta = $pdo->prepare("SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = :email");
    $consulta->execute([':email' => $email]);
    $usr = $consulta->fetch();

    if ($usr && password_verify($senha, $usr['senha_hash'])) {
        iniciar_sessao_usuario($usr);
        redirecionar('index.php');
    }
    $erro = 'E-mail ou senha incorretos.';
}

$titulo_pagina = 'Entrar';
$pagina_auth   = true;
require_once __DIR__ . '/topo.php';
?>

<aside class="auth-lado">
  <a class="marca marca-clara" href="login.php">Trata</a>
  <div class="auth-pilha" aria-hidden="true">
    <?= capa(['titulo' => 'Dom Casmurro', 'autor' => 'Machado de Assis']) ?>
    <?= capa(['titulo' => 'O Hobbit', 'autor' => 'J. R. R. Tolkien']) ?>
    <?= capa(['titulo' => '1984', 'autor' => 'George Orwell']) ?>
  </div>
  <p class="auth-frase">Um acervo para explorar, avaliar e compartilhar.</p>
</aside>

<section class="auth-form">
  <div class="auth-caixa">
    <h1>Entrar</h1>
    <p class="sub">Acesse sua conta para ver o acervo.</p>

    <?php if ($flash): ?><div class="alerta alerta-<?= $flash['tipo'] === 'erro' ? 'erro' : 'ok' ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alerta alerta-erro" role="alert"><?= e($erro) ?></div><?php endif; ?>

    <form method="post" class="form">
      <?= csrf_campo() ?>
      <label>E-mail
        <input type="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="email">
      </label>
      <label>Senha
        <input type="password" name="senha" required autocomplete="current-password">
      </label>
      <button class="btn btn-primario btn-bloco">Entrar</button>
    </form>

    <p class="auth-rodape">Ainda não tem conta? <a href="cadastro.php">Criar conta</a></p>
  </div>
</section>

<?php require_once __DIR__ . '/rodape.php'; ?>
