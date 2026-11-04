/* Trata · preferências de leitura (tamanho, fonte e largura), guardadas neste aparelho */
(function () {
  var CHAVE = 'trata_leitor';
  var padrao = { fs: 19, fam: 'serif', w: 'normal' };

  function ler() {
    try { return Object.assign({}, padrao, JSON.parse(localStorage.getItem(CHAVE) || '{}')); }
    catch (e) { return Object.assign({}, padrao); }
  }
  function salvar(p) { try { localStorage.setItem(CHAVE, JSON.stringify(p)); } catch (e) {} }

  document.addEventListener('DOMContentLoaded', function () {
    var alvos = document.querySelectorAll('[data-leitor]');
    var p = ler();

    function aplicar() {
      alvos.forEach(function (alvo) {
        alvo.style.setProperty('--leitor-fs', p.fs + 'px');
        alvo.dataset.fam = p.fam;
        alvo.dataset.larg = p.w;
      });
      document.querySelectorAll('[data-lt]').forEach(function (b) {
        var a = b.dataset.lt, on = (a === p.fam) || (a === p.w);
        b.classList.toggle('ativo', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
    }
    aplicar();

    document.addEventListener('click', function (ev) {
      var b = ev.target.closest('[data-lt]');
      if (!b) return;
      var a = b.dataset.lt;
      if (a === 'menos') p.fs = Math.max(14, p.fs - 1);
      else if (a === 'mais') p.fs = Math.min(30, p.fs + 1);
      else if (a === 'serif' || a === 'sans') p.fam = a;
      else p.w = a;
      salvar(p);
      aplicar();
    });
  });
})();
