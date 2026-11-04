</main>
<?php if (empty($pagina_auth)): ?>
<footer class="rodape">
  <span class="rodape-marca">Trata</span>
  <span>Biblioteca escolar · © <?= date('Y') ?></span>
</footer>
<?php endif; ?>
<script src="leitor.js"></script>
<script>
(function () {
  var raiz = document.documentElement;

  // ---- Tema: troca na hora, com transição suave, e guarda em cookie ----
  document.addEventListener('change', function (ev) {
    var r = ev.target;
    if (!r.matches || !r.matches('input[data-tema-opcao]')) return;
    raiz.classList.add('trocando');
    document.cookie = 'trata_tema=' + r.value + '; path=/; max-age=31536000; SameSite=Lax';
    raiz.dataset.tema = r.value;
    document.querySelectorAll('input[data-tema-opcao]').forEach(function (o) { o.checked = (o.value === r.value); });
    setTimeout(function () { raiz.classList.remove('trocando'); }, 600);
  });

  // ---- Menu do usuário: fecha ao clicar fora ----
  var menu = document.querySelector('.usuario-menu');
  if (menu) document.addEventListener('click', function (ev) { if (!menu.contains(ev.target)) menu.removeAttribute('open'); });

  // ---- Painel de configurações ----
  var painel = document.getElementById('painel-config');
  var fundo  = document.querySelector('.painel-fundo');
  var gatilho = document.querySelector('[data-abrir-config]');
  var ultimoFoco = null;

  function abrir() {
    if (!painel) return;
    ultimoFoco = document.activeElement;
    if (menu) menu.removeAttribute('open');
    painel.removeAttribute('inert');
    painel.setAttribute('aria-hidden', 'false');
    painel.classList.add('aberto');
    fundo.classList.add('aberto');
    document.body.classList.add('sem-rolagem');
    if (gatilho) gatilho.setAttribute('aria-expanded', 'true');
    setTimeout(function () { var f = painel.querySelector('.painel-fechar'); if (f) f.focus(); }, 60);
  }
  function fechar() {
    if (!painel || !painel.classList.contains('aberto')) return;
    painel.classList.remove('aberto');
    fundo.classList.remove('aberto');
    painel.setAttribute('aria-hidden', 'true');
    painel.setAttribute('inert', '');
    document.body.classList.remove('sem-rolagem');
    if (gatilho) gatilho.setAttribute('aria-expanded', 'false');
    if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
  }
  document.addEventListener('click', function (ev) {
    var a = ev.target.closest('[data-abrir-config]');
    if (a) { ev.preventDefault(); painel.classList.contains('aberto') ? fechar() : abrir(); return; }
    if (ev.target.closest('[data-fechar-config]')) fechar();
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') { fechar(); if (menu) menu.removeAttribute('open'); }
  });
})();
</script>
</body>
</html>
