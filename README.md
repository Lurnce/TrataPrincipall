# Trata

Sistema de biblioteca escolar em PHP + MariaDB (XAMPP): acervo, alunos, empréstimos, login, perfil, avaliações e recomendações.

## Como instalar

1. Copie a pasta do projeto para `C:\xampp\htdocs\trata` (substitua os arquivos antigos).
2. Inicie **Apache** e **MySQL/MariaDB** no XAMPP.
3. No phpMyAdmin, abra a aba **SQL** e execute, nesta ordem:
   1. `banco.sql`: estrutura, descrição e 25 livros.
   2. `atualizacao.sql`: resumos, leitor, suporte e mais 13 livros.
   Os dois são seguros para rodar sobre o banco atual e mais de uma vez.
4. Se necessário, ajuste usuário/senha do banco em `conexao.php`.
5. Acesse `http://localhost/trata/` e entre ou crie sua conta.
   **A primeira conta criada vira bibliotecário (admin)**; as seguintes são leitores.

## O que mudou

- Visual novo: capas tipográficas geradas em CSS, página inicial com busca e filtro por gênero, prateleiras "Escolhidos para você" e "Mais bem avaliados".
- Página do livro com descrição, avaliações, distribuição das notas e livros do mesmo gênero.
- Login e cadastro em tela dividida; menu do usuário no cabeçalho.
- Perfil (`perfil.php`): editar nome, bio e gênero favorito, alterar senha, ver avaliações e empréstimos.
- Descrição editável no cadastro e na edição de livros.
- Alunos: turma obrigatória (como no banco) e vínculo opcional com uma conta de leitor.

## Novidades da versão 3 (visual)

- Nova tipografia: **Instrument Serif** nos títulos e **Hanken Grotesk** no texto.
- Cabeçalho de vidro, hero com gradiente animado, capas com brilho e cartões mais arredondados.
- Mais fluidez: transição suave entre páginas, entrada animada dos cartões, troca de tema com fade, barra de progresso na leitura.
- **Painel de configurações** que abre de qualquer página pelo ícone de ajustes: tema (claro, escuro, automático), leitura, idioma, ajuda, relatar bug e sugestão.

## Novidades da versão 2

- **Configurações** (ícone no cabeçalho): tema claro, escuro ou automático, idioma, preferências de leitura, conta e ajuda.
- **Ajuda e suporte** (`suporte.php`): perguntas frequentes, envio de bugs, sugestões e dúvidas. O bibliotecário vê todos os relatos e marca como resolvidos.
- **Leitor** (`ler.php`): capítulos, ajuste de letra/fonte/largura e progresso salvo ("Continue lendo" na página inicial).
- **Gerenciar texto** (`conteudo.php`, bibliotecário): cole o texto ou envie um .txt, com cada capítulo iniciado por `## Título`.
- **Resumo da história** em cada livro (com aviso de spoilers) e mais livros no acervo.

Importante: o sistema não traz o texto completo dos livros. Só publique obras de domínio público
(Domínio Público, Projeto Gutenberg, Wikisource) ou com autorização. O livro "Guia do Leitor Trata" já vem com texto de exemplo.

## Recomendações

Peso por gênero: gênero favorito (3), cada nota 4 ou 5 (2), cada empréstimo do aluno vinculado à conta (1) e cada livro que você começou a ler (1).
Sem nenhum sinal, a página inicial mostra um cartão para escolher o gênero favorito.
Entram livros desses gêneros que o usuário ainda não avaliou nem pegou emprestado.
Para os empréstimos contarem, vincule a conta do leitor ao aluno em **Alunos**.

## Perfis

| Perfil | Pode |
|---|---|
| Bibliotecário (admin) | Tudo: livros, alunos, empréstimos, moderar avaliações |
| Leitor | Ver o acervo, avaliar livros, editar o próprio perfil |

Para promover alguém a bibliotecário:

```sql
UPDATE usuarios SET perfil = 'admin' WHERE email = 'email@exemplo.com';
```

## Estrutura

- `conexao.php`: banco, sessão, login, CSRF, recomendações e funções auxiliares
- `componentes.php`: capa e cartão de livro
- `topo.php` / `rodape.php`: layout comum
- `login.php`, `cadastro.php`, `sair.php`, `perfil.php`: conta
- `configuracoes.php`, `suporte.php`: preferências e ajuda
- `ler.php`, `conteudo.php`, `leitor.js`: leitura de livros
- `index.php`, `livro.php`, `novo.php`, `editar.php`, `excluir.php`, `form_livro.php`: acervo
- `emprestimos.php`, `alunos.php`: gestão da biblioteca
- `estilo.css`, `banco.sql`, `atualizacao.sql`
