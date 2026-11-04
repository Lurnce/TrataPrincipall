-- Trata · estrutura do banco + acervo inicial
-- Seguro para rodar sobre o banco existente: só cria o que ainda não existe
-- e só preenche a descrição de livros que ainda não têm uma.

CREATE DATABASE IF NOT EXISTS biblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca;

CREATE TABLE IF NOT EXISTS livros (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  titulo     VARCHAR(150) NOT NULL,
  autor      VARCHAR(100) NOT NULL,
  ano        INT,
  genero     VARCHAR(50),
  quantidade INT NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS alunos (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  nome  VARCHAR(100) NOT NULL,
  turma VARCHAR(20) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS emprestimos (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  livro_id        INT NOT NULL,
  aluno_id        INT NOT NULL,
  data_emprestimo DATE NOT NULL,
  data_devolucao  DATE NULL,
  FOREIGN KEY (livro_id) REFERENCES livros(id),
  FOREIGN KEY (aluno_id) REFERENCES alunos(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  perfil     ENUM('admin','leitor') NOT NULL DEFAULT 'leitor',
  criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS avaliacoes (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  livro_id      INT NOT NULL,
  usuario_id    INT NOT NULL,
  nota          TINYINT NOT NULL,
  comentario    VARCHAR(500) NULL,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_livro_usuario (livro_id, usuario_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Perfil e recomendações ----------
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS bio VARCHAR(200) NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS genero_favorito VARCHAR(50) NULL;

-- Liga a conta de um leitor ao cadastro do aluno (usa os empréstimos nas recomendações)
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS usuario_id INT NULL;
ALTER TABLE alunos ADD UNIQUE INDEX IF NOT EXISTS uq_alunos_usuario (usuario_id);

-- ---------- NOVO: descrição (sinopse) dos livros ----------
ALTER TABLE livros ADD COLUMN IF NOT EXISTS descricao TEXT NULL;

-- ---------- NOVO: acervo inicial ----------
DROP TEMPORARY TABLE IF EXISTS seed_livros;
CREATE TEMPORARY TABLE seed_livros (
  titulo     VARCHAR(150),
  autor      VARCHAR(100),
  ano        INT,
  genero     VARCHAR(50),
  quantidade INT,
  descricao  TEXT
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO seed_livros (titulo, autor, ano, genero, quantidade, descricao) VALUES
('Dom Casmurro', 'Machado de Assis', 1899, 'Romance', 3,
 'Já adulto, Bentinho recorda a juventude e o amor por Capitu, a vizinha que se tornou sua esposa. Tomado pelo ciúme, ele conta a história do seu jeito, e cabe ao leitor decidir se a sua versão merece confiança.'),
('Memórias Póstumas de Brás Cubas', 'Machado de Assis', 1881, 'Romance', 2,
 'Um defunto autor narra a própria vida de além-túmulo, com ironia e humor ácido. Um retrato debochado da elite do Rio de Janeiro no século XIX.'),
('O Cortiço', 'Aluísio Azevedo', 1890, 'Romance', 2,
 'No Rio de Janeiro do século XIX, o ambicioso João Romão enriquece enquanto os moradores de um cortiço vivem entre a pobreza, os conflitos e a luta por uma vida melhor. Um dos grandes romances naturalistas brasileiros.'),
('Capitães da Areia', 'Jorge Amado', 1937, 'Romance', 3,
 'Um grupo de meninos de rua de Salvador, liderado por Pedro Bala, vive num trapiche abandonado e sobrevive de pequenos roubos. Uma história sobre infância, liberdade e injustiça social.'),
('Vidas Secas', 'Graciliano Ramos', 1938, 'Romance', 3,
 'Fabiano, Sinhá Vitória, os dois meninos e a cachorra Baleia atravessam o sertão nordestino fugindo da seca. Com uma linguagem direta e econômica, o livro mostra a dureza da vida dos retirantes.'),
('A Hora da Estrela', 'Clarice Lispector', 1977, 'Romance', 2,
 'Macabéa é uma jovem nordestina que trabalha como datilógrafa no Rio de Janeiro e leva uma vida simples e solitária. O narrador, Rodrigo S.M., reflete sobre ela e sobre o próprio ato de escrever.'),
('O Alienista', 'Machado de Assis', 1882, 'Conto', 2,
 'O médico Simão Bacamarte cria um hospício na vila de Itaguaí e passa a internar quem foge ao que ele considera razoável. Uma sátira divertida sobre ciência, poder e os limites entre sanidade e loucura.'),
('Iracema', 'José de Alencar', 1865, 'Romance', 2,
 'No Ceará, a indígena Iracema se apaixona por Martim, um guerreiro português, e o amor dos dois muda o destino de ambos. Romance indianista que também conta, em forma de lenda, a origem do povo cearense.'),
('O Pequeno Príncipe', 'Antoine de Saint-Exupéry', 1943, 'Infantojuvenil', 4,
 'Um piloto cai com o avião no deserto e conhece um menino vindo de outro planeta. Nas conversas entre os dois, surgem reflexões sobre amizade, amor e o que realmente importa.'),
('Alice no País das Maravilhas', 'Lewis Carroll', 1865, 'Fantasia', 3,
 'Ao seguir um coelho branco, Alice cai numa toca e chega a um mundo onde a lógica funciona ao contrário. No caminho, encontra personagens inesquecíveis, como o Chapeleiro Maluco e a Rainha de Copas.'),
('Harry Potter e a Pedra Filosofal', 'J. K. Rowling', 1997, 'Fantasia', 4,
 'Aos onze anos, Harry descobre que é bruxo e vai estudar em Hogwarts. Lá faz amigos, aprende magia e se envolve no mistério da pedra filosofal.'),
('O Hobbit', 'J. R. R. Tolkien', 1937, 'Fantasia', 3,
 'O pacato hobbit Bilbo Bolseiro é convidado pelo mago Gandalf e por treze anões a recuperar um tesouro guardado por um dragão. Uma aventura que o tira da toca e o transforma.'),
('Reinações de Narizinho', 'Monteiro Lobato', 1931, 'Infantojuvenil', 3,
 'Lúcia, a Narizinho, vive aventuras no Sítio do Picapau Amarelo ao lado da boneca falante Emília e do Visconde de Sabugosa. Porta de entrada para a fantasia brasileira de Monteiro Lobato.'),
('Meu Pé de Laranja Lima', 'José Mauro de Vasconcelos', 1968, 'Infantojuvenil', 3,
 'Zezé é um menino esperto e travesso que encontra consolo na imaginação, em um pé de laranja lima e na amizade com o Portuga. Uma história comovente sobre infância e pobreza.'),
('1984', 'George Orwell', 1949, 'Distopia', 3,
 'Winston Smith vive em um país vigiado pelo Grande Irmão, onde o Partido controla os fatos, a linguagem e até os pensamentos. Ao começar a questionar o sistema, ele passa a correr perigo.'),
('A Revolução dos Bichos', 'George Orwell', 1945, 'Distopia', 3,
 'Os animais de uma fazenda expulsam o dono para viver em igualdade, mas aos poucos os porcos assumem o comando. Uma fábula curta e afiada sobre poder e manipulação.'),
('Frankenstein', 'Mary Shelley', 1818, 'Ficção científica', 2,
 'O cientista Victor Frankenstein dá vida a uma criatura feita de partes de corpos e foge de sua própria criação. O romance questiona a responsabilidade da ciência e o sofrimento de quem é rejeitado.'),
('Um Estudo em Vermelho', 'Arthur Conan Doyle', 1887, 'Policial', 2,
 'O doutor Watson conhece Sherlock Holmes e os dois investigam um misterioso assassinato em Londres. É a primeira aventura da dupla de detetives mais famosa da literatura.'),
('Assassinato no Expresso do Oriente', 'Agatha Christie', 1934, 'Policial', 2,
 'Um trem fica preso na neve e um passageiro é encontrado morto. O detetive Hercule Poirot precisa descobrir quem, entre os viajantes, cometeu o crime.'),
('O Diário de Anne Frank', 'Anne Frank', 1947, 'Biografia', 3,
 'Anne, uma menina judia, escreve sobre o dia a dia escondida com a família em Amsterdã durante a Segunda Guerra Mundial. Um testemunho sensível sobre medo, esperança e crescimento.'),
('A Menina que Roubava Livros', 'Markus Zusak', 2005, 'Romance', 3,
 'Na Alemanha nazista, Liesel Meminger aprende a ler e começa a roubar livros. A história é narrada pela própria Morte e mostra o poder das palavras em tempos difíceis.'),
('O Homem que Calculava', 'Malba Tahan', 1938, 'Aventura', 2,
 'O calculista Beremiz Samir viaja pelo Oriente e resolve desafios e enigmas com a matemática. Entre histórias e lendas, o livro mostra como os números podem ser surpreendentes.'),
('Cosmos', 'Carl Sagan', 1980, 'Ciência', 2,
 'Carl Sagan conduz o leitor por uma viagem pelo universo, das estrelas à origem da vida. Um clássico da divulgação científica sobre o nosso lugar no cosmos.'),
('Orgulho e Preconceito', 'Jane Austen', 1813, 'Romance', 3,
 'Elizabeth Bennet e o reservado Sr. Darcy se desentendem logo no primeiro encontro. Entre bailes e visitas na Inglaterra rural, ambos precisam rever suas primeiras impressões.'),
('A Ilha do Tesouro', 'Robert Louis Stevenson', 1883, 'Aventura', 2,
 'O jovem Jim Hawkins encontra um mapa do tesouro e embarca em uma viagem cheia de piratas, entre eles o carismático Long John Silver. A aventura marítima que definiu o gênero.');

-- Insere só os livros que ainda não existem (pelo título)
INSERT INTO livros (titulo, autor, ano, genero, quantidade, descricao)
SELECT s.titulo, s.autor, s.ano, s.genero, s.quantidade, s.descricao
FROM seed_livros s
WHERE NOT EXISTS (SELECT 1 FROM livros l WHERE l.titulo = s.titulo COLLATE utf8mb4_unicode_ci);

-- Preenche a descrição de livros que já existiam sem ela
UPDATE livros l
JOIN seed_livros s ON s.titulo COLLATE utf8mb4_unicode_ci = l.titulo
SET l.descricao = s.descricao
WHERE l.descricao IS NULL OR l.descricao = '';

DROP TEMPORARY TABLE IF EXISTS seed_livros;

-- Para promover outro usuário a bibliotecário (admin):
-- UPDATE usuarios SET perfil = 'admin' WHERE email = 'email@exemplo.com';
