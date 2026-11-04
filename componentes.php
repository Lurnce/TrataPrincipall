<?php
/* Trata · componentes visuais reutilizáveis (capa e cartão de livro) */

// Capa tipográfica gerada em CSS: faixas coloridas + título, cor definida pelo título
function capa(array $livro, string $extra = ''): string {
    $cor = abs(crc32((string)$livro['titulo'])) % 8;
    return '<div class="capa capa-' . $cor . ($extra !== '' ? ' ' . e($extra) : '') . '" aria-hidden="true">'
         . '<span class="capa-faixa"></span>'
         . '<span class="capa-meio"><b>' . e($livro['titulo']) . '</b><i>' . e($livro['autor']) . '</i></span>'
         . '<span class="capa-faixa"></span></div>';
}

function cartao_livro(array $l): string {
    $disp = max(0, (int)$l['quantidade'] - (int)$l['emprestados']);
    $url  = 'livro.php?id=' . (int)$l['id'];
    ob_start(); ?>
    <article class="livro">
      <a class="livro-capa" href="<?= $url ?>" tabindex="-1"><?= capa($l) ?><?php if ((int)($l['capitulos'] ?? 0) > 0): ?><span class="selo-leitura">Leitura</span><?php endif; ?></a>
      <div class="livro-info">
        <h3><a href="<?= $url ?>"><?= e($l['titulo']) ?></a></h3>
        <p class="autor"><?= e($l['autor']) ?></p>
        <div class="nota-linha">
          <?php if ((int)$l['total_aval'] > 0): ?>
            <?= estrelas($l['media']) ?>
            <small><?= number_format((float)$l['media'], 1, ',', '.') ?> (<?= (int)$l['total_aval'] ?>)</small>
          <?php else: ?>
            <small class="mudo">Sem avaliações</small>
          <?php endif; ?>
        </div>
        <span class="status <?= $disp > 0 ? 'status-ok' : 'status-off' ?>">
          <?= $disp > 0 ? $disp . ($disp === 1 ? ' disponível' : ' disponíveis') : 'Indisponível' ?>
        </span>
      </div>
    </article>
    <?php
    return ob_get_clean();
}
