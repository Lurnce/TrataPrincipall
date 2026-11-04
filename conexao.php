<?php
/* =========================================================
   Trata · conexão, sessão e funções auxiliares
   ========================================================= */

// ---------- Sessão ----------
session_name('trata_sessao');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

const PRAZO_DIAS = 14; // prazo padrão de devolução

// ---------- Banco de dados ----------
$host    = 'localhost';
$db      = 'biblioteca';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Trata: falha na conexão - ' . $e->getMessage());
    http_response_code(500);
    die('Não foi possível conectar ao banco de dados. Verifique se o MariaDB está ativo no XAMPP e se o banco "biblioteca" foi criado (veja banco.sql).');
}

// ---------- Utilidades ----------
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

function campo(string $nome, bool $aparar = true): string {
    $v = $_POST[$nome] ?? '';
    if (!is_string($v)) return '';
    return $aparar ? trim($v) : $v;
}

function redirecionar(string $url): void {
    header('Location: ' . $url);
    exit;
}

function data_br($data): string {
    return $data ? date('d/m/Y', strtotime($data)) : '';
}

function primeiro_nome(string $nome): string {
    $partes = preg_split('/\s+/', trim($nome));
    return $partes[0] ?? '';
}

function flash(string $msg, string $tipo = 'ok'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'tipo' => $tipo];
}
function pegar_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ---------- Autenticação ----------
function usuario_logado(): ?array {
    return $_SESSION['usuario'] ?? null;
}
function eh_admin(): bool {
    return (usuario_logado()['perfil'] ?? '') === 'admin';
}
function exigir_login(): void {
    if (!usuario_logado()) redirecionar('login.php');
}
function exigir_admin(): void {
    exigir_login();
    if (!eh_admin()) {
        flash('Você não tem permissão para acessar essa página.', 'erro');
        redirecionar('index.php');
    }
}
function iniciar_sessao_usuario(array $usr): void {
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'     => (int)$usr['id'],
        'nome'   => $usr['nome'],
        'email'  => $usr['email'],
        'perfil' => $usr['perfil'],
    ];
}

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_campo(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_validar(): void {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        die('Sessão expirada. Volte à página anterior, recarregue e tente novamente.');
    }
}

// ---------- Avaliação em estrelas ----------
function estrelas($media): string {
    $n = max(0, min(5, (float)$media));
    return '<span class="estrelas" style="--n:' . $n . '" aria-label="Nota '
         . number_format($n, 1, ',', '.') . ' de 5">★★★★★</span>';
}

// ---------- Consulta base de livros (com disponibilidade e média) ----------
const SQL_LIVRO = "SELECT l.id, l.titulo, l.autor, l.ano, l.genero, l.quantidade, l.descricao, l.resumo,
        (SELECT COUNT(*) FROM emprestimos e
          WHERE e.livro_id = l.id AND e.data_devolucao IS NULL) AS emprestados,
        (SELECT ROUND(AVG(a.nota), 1) FROM avaliacoes a WHERE a.livro_id = l.id) AS media,
        (SELECT COUNT(*) FROM avaliacoes a WHERE a.livro_id = l.id) AS total_aval,
        (SELECT COUNT(*) FROM capitulos c WHERE c.livro_id = l.id) AS capitulos
     FROM livros l";

function lista_generos(PDO $pdo): array {
    return $pdo->query("SELECT DISTINCT genero FROM livros
                         WHERE genero IS NOT NULL AND genero <> '' ORDER BY genero")->fetchAll(PDO::FETCH_COLUMN);
}

// ---------- Recomendações ----------
// Peso de cada gênero para o usuário: gênero favorito (3), cada nota >= 4 (2)
// e cada empréstimo feito pelo aluno vinculado à conta (1).
function pesos_de_generos(PDO $pdo, int $uid): array {
    $pesos = [];

    $c = $pdo->prepare("SELECT genero_favorito FROM usuarios WHERE id = :u");
    $c->execute([':u' => $uid]);
    $fav = (string)$c->fetchColumn();
    if ($fav !== '') $pesos[$fav] = ($pesos[$fav] ?? 0) + 3;

    $c = $pdo->prepare("SELECT l.genero, COUNT(*) AS n
                        FROM avaliacoes a JOIN livros l ON l.id = a.livro_id
                        WHERE a.usuario_id = :u AND a.nota >= 4 AND l.genero IS NOT NULL AND l.genero <> ''
                        GROUP BY l.genero");
    $c->execute([':u' => $uid]);
    foreach ($c->fetchAll() as $r) $pesos[$r['genero']] = ($pesos[$r['genero']] ?? 0) + 2 * (int)$r['n'];

    $c = $pdo->prepare("SELECT l.genero, COUNT(*) AS n
                        FROM emprestimos e
                        JOIN alunos al ON al.id = e.aluno_id
                        JOIN livros l  ON l.id = e.livro_id
                        WHERE al.usuario_id = :u AND l.genero IS NOT NULL AND l.genero <> ''
                        GROUP BY l.genero");
    $c->execute([':u' => $uid]);
    foreach ($c->fetchAll() as $r) $pesos[$r['genero']] = ($pesos[$r['genero']] ?? 0) + (int)$r['n'];

    // Livros que o usuário começou a ler (1 por livro)
    $c = $pdo->prepare("SELECT l.genero, COUNT(*) AS n
                        FROM progresso p JOIN livros l ON l.id = p.livro_id
                        WHERE p.usuario_id = :u AND l.genero IS NOT NULL AND l.genero <> ''
                        GROUP BY l.genero");
    $c->execute([':u' => $uid]);
    foreach ($c->fetchAll() as $r) $pesos[$r['genero']] = ($pesos[$r['genero']] ?? 0) + (int)$r['n'];

    arsort($pesos);
    return $pesos;
}

// Livros dos gêneros preferidos que o usuário ainda não avaliou nem pegou emprestado
function recomendados(PDO $pdo, int $uid, int $limite = 4): array {
    $pesos = pesos_de_generos($pdo, $uid);
    if (!$pesos) return [];

    $c = $pdo->prepare(SQL_LIVRO . "
        WHERE l.genero IS NOT NULL
          AND NOT EXISTS (SELECT 1 FROM avaliacoes a
                           WHERE a.livro_id = l.id AND a.usuario_id = :u1)
          AND NOT EXISTS (SELECT 1 FROM emprestimos e JOIN alunos al ON al.id = e.aluno_id
                           WHERE e.livro_id = l.id AND al.usuario_id = :u2)");
    $c->execute([':u1' => $uid, ':u2' => $uid]);

    $lista = [];
    foreach ($c->fetchAll() as $l) {
        $p = $pesos[$l['genero']] ?? 0;
        if ($p <= 0) continue;
        $l['_score'] = $p;
        $lista[] = $l;
    }
    usort($lista, fn($a, $b) =>
        [$b['_score'], (float)$b['media'], $a['titulo']] <=> [$a['_score'], (float)$a['media'], $b['titulo']]);
    return array_slice($lista, 0, $limite);
}

// ---------- Validação de livro (usada em novo.php e editar.php) ----------
function validar_livro(): array {
    $d = [
        'titulo'     => campo('titulo'),
        'autor'      => campo('autor'),
        'ano'        => campo('ano'),
        'genero'     => campo('genero'),
        'quantidade' => campo('quantidade'),
        'descricao'  => campo('descricao'),
        'resumo'     => campo('resumo'),
    ];
    $erros = [];
    if ($d['titulo'] === '' || mb_strlen($d['titulo']) > 150) $erros[] = 'Informe o título (até 150 caracteres).';
    if ($d['autor'] === '' || mb_strlen($d['autor']) > 100)   $erros[] = 'Informe o autor (até 100 caracteres).';
    if ($d['ano'] !== '' && (!ctype_digit($d['ano']) || (int)$d['ano'] < 1000 || (int)$d['ano'] > (int)date('Y') + 1)) {
        $erros[] = 'Ano inválido.';
    }
    if (mb_strlen($d['genero']) > 50) $erros[] = 'O gênero pode ter até 50 caracteres.';
    if (!ctype_digit($d['quantidade']) || (int)$d['quantidade'] < 1 || (int)$d['quantidade'] > 9999) {
        $erros[] = 'A quantidade deve ser um número maior que zero.';
    }
    if (mb_strlen($d['descricao']) > 1500) $erros[] = 'A descrição pode ter até 1.500 caracteres.';
    if (mb_strlen($d['resumo']) > 3000) $erros[] = 'O resumo pode ter até 3.000 caracteres.';
    return [$d, $erros];
}

// ---------- Tema (claro, escuro ou automático) ----------
function tema_atual(): string {
    $t = $_COOKIE['trata_tema'] ?? 'auto';
    return in_array($t, ['claro', 'escuro', 'auto'], true) ? $t : 'auto';
}

// ---------- Leitor: divide um texto em capítulos ----------
// Cada linha iniciada por "##" começa um capítulo novo ("## Título do capítulo").
function dividir_capitulos(string $txt): array {
    $txt    = str_replace(["\r\n", "\r"], "\n", trim($txt));
    $partes = preg_split('/^##[ \t]*(.*)$/m', $txt, -1, PREG_SPLIT_DELIM_CAPTURE);
    $caps   = [];
    $antes  = trim($partes[0] ?? '');
    if ($antes !== '') {
        $caps[] = ['titulo' => count($partes) > 1 ? 'Início' : 'Texto completo', 'texto' => $antes];
    }
    for ($i = 1; $i < count($partes); $i += 2) {
        $corpo = trim($partes[$i + 1] ?? '');
        if ($corpo === '') continue;
        $titulo = trim($partes[$i]);
        if ($titulo === '') $titulo = 'Capítulo ' . (count($caps) + 1);
        $caps[] = ['titulo' => mb_substr($titulo, 0, 150), 'texto' => $corpo];
    }
    return $caps;
}

const VERSAO_APP = '2.0';

require_once __DIR__ . '/componentes.php';
