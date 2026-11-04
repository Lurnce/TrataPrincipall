-- =====================================================================
-- Trata · SQL COMPLETO (banco.sql + atualizacao.sql em um arquivo só)
-- Cole na aba SQL do phpMyAdmin (ou use a aba Importar) e execute.
-- É seguro rodar sobre o banco existente: só cria o que falta e não apaga dados.
--
-- COMEÇAR DO ZERO (apaga TUDO: livros, alunos, empréstimos, contas):
-- tire os dois traços da linha abaixo antes de executar.
-- DROP DATABASE IF EXISTS biblioteca;
-- =====================================================================

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


-- Trata · atualização 2 (rode DEPOIS do banco.sql; é seguro rodar mais de uma vez)
-- Adiciona: resumo da história, leitor de livros, progresso de leitura,
-- central de suporte/relatos e mais livros no acervo.

USE biblioteca;

-- ---------- Estrutura ----------
ALTER TABLE livros ADD COLUMN IF NOT EXISTS resumo TEXT NULL;

CREATE TABLE IF NOT EXISTS capitulos (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  livro_id INT NOT NULL,
  numero   INT NOT NULL,
  titulo   VARCHAR(150) NOT NULL,
  texto    MEDIUMTEXT NOT NULL,
  UNIQUE KEY uniq_livro_numero (livro_id, numero),
  FOREIGN KEY (livro_id) REFERENCES livros(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS progresso (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id    INT NOT NULL,
  livro_id      INT NOT NULL,
  capitulo      INT NOT NULL DEFAULT 1,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_progresso (usuario_id, livro_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (livro_id)   REFERENCES livros(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS relatos (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  tipo       ENUM('bug','sugestao','duvida') NOT NULL DEFAULT 'bug',
  titulo     VARCHAR(120) NOT NULL,
  descricao  VARCHAR(2000) NOT NULL,
  pagina     VARCHAR(200) NULL,
  status     ENUM('aberto','resolvido') NOT NULL DEFAULT 'aberto',
  criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Livros novos ----------
DROP TEMPORARY TABLE IF EXISTS seed_livros;
CREATE TEMPORARY TABLE seed_livros (
  titulo VARCHAR(150), autor VARCHAR(100), ano INT, genero VARCHAR(50),
  quantidade INT, descricao TEXT, resumo TEXT
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO seed_livros (titulo, autor, ano, genero, quantidade, descricao, resumo) VALUES
('O Guarani', 'José de Alencar', 1857, 'Romance', 2,
 'No século XVII, o indígena Peri dedica a vida a proteger Ceci, filha de um fidalgo português, em um romance de aventura e lealdade ambientado na natureza brasileira.',
 'Peri, um indígena goitacá, jura proteger Cecília, a filha de dom Antônio de Mariz, que vive numa casa fortificada perto do rio Paquequer. A fazenda é ameaçada por indígenas inimigos e por traições dentro da própria casa. Entre a lealdade de Peri e o afeto de Ceci, a história chega a um desfecho dramático em que ele arrisca tudo para salvá-la.'),
('Quincas Borba', 'Machado de Assis', 1891, 'Romance', 2,
 'Rubião herda a fortuna do filósofo Quincas Borba, com a condição de cuidar do cachorro que leva o mesmo nome, e se muda para o Rio de Janeiro, onde vira alvo de falsos amigos.',
 'O professor Rubião herda tudo do amigo Quincas Borba, criador da filosofia do Humanitismo, e vai viver na corte. Apaixonado por Sofia, esposa do sócio Palha, ele é explorado pelo casal e perde aos poucos a fortuna e a razão. Um retrato irônico da ambição e da vaidade.'),
('Triste Fim de Policarpo Quaresma', 'Lima Barreto', 1915, 'Romance', 2,
 'O major Quaresma, funcionário público e patriota sincero, tenta valorizar o Brasil de todas as maneiras e vai se desiludindo com o país real.',
 'Policarpo Quaresma estuda tudo o que é brasileiro: propõe o tupi como língua oficial, tenta viver da terra e depois se envolve na política da época de Floriano Peixoto. Cada projeto fracassa diante da burocracia e do descaso. O livro termina com Quaresma preso e desiludido.'),
('Memórias de um Sargento de Milícias', 'Manuel Antônio de Almeida', 1854, 'Romance', 2,
 'Leonardo, um jovem travesso e sem muita sorte, se mete em confusões e paixões no Rio de Janeiro do tempo do rei dom João VI.',
 'Criado pelo padrinho barbeiro e pela madrinha, Leonardo foge dos estudos, aprende a viver de pequenos golpes e se apaixona por Luisinha. As trapalhadas o põem na mira do severo Major Vidigal, chefe da polícia. No fim, ele acaba incorporado às milícias e ganha estabilidade.'),
('A Metamorfose', 'Franz Kafka', 1915, 'Conto', 2,
 'Gregor Samsa acorda transformado em um inseto gigante e vê a família e o trabalho reagirem à nova condição. Uma novela sobre isolamento, culpa e identidade.',
 'Gregor, caixeiro-viajante que sustenta a família, não consegue mais sair do quarto. A irmã, Grete, cuida dele no início, mas os pais e ela vão se cansando. Isolado, ele definha e morre, e a família, aliviada, volta a olhar para o futuro.'),
('As Aventuras de Tom Sawyer', 'Mark Twain', 1876, 'Aventura', 3,
 'Em uma cidadezinha às margens do Mississippi, o travesso Tom Sawyer vive aventuras com o amigo Huck Finn, de fugas e tesouros a um crime presenciado no cemitério.',
 'Tom mora com a tia Polly, foge da escola, convence os colegas a pintar a cerca por ele e se apaixona por Becky Thatcher. Uma noite, ele e Huck presenciam um assassinato e guardam o segredo. Depois de se perderem numa caverna, os garotos encontram um tesouro.'),
('Drácula', 'Bram Stoker', 1897, 'Terror', 2,
 'O advogado Jonathan Harker viaja à Transilvânia e descobre que o conde que o recebe é um vampiro decidido a se mudar para Londres. O romance é contado em diários e cartas.',
 'Preso no castelo do conde, Harker consegue escapar. Drácula chega à Inglaterra e ataca Lucy, amiga de Mina Harker. O doutor Van Helsing reúne um grupo para detê-lo, e a perseguição leva todos de volta à Transilvânia, onde o conde é derrotado.'),
('Vinte Mil Léguas Submarinas', 'Júlio Verne', 1870, 'Ficção científica', 2,
 'O professor Aronnax e seus companheiros são recolhidos pelo submarino Nautilus e viajam pelos oceanos com o enigmático capitão Nemo.',
 'Um navio sai para caçar um suposto monstro marinho, mas o monstro é o Nautilus. Aronnax, seu criado Conselho e o arpoador Ned Land passam a viver a bordo, vendo maravilhas submarinas, de recifes a ruínas e ao Polo Sul. Ned planeja a fuga, que acontece em meio a uma tempestade.'),
('A Máquina do Tempo', 'H. G. Wells', 1895, 'Ficção científica', 2,
 'Um inventor constrói uma máquina capaz de viajar no tempo e chega a um futuro distante, onde descobre que a humanidade se dividiu em dois povos.',
 'O Viajante do Tempo vai parar no ano 802.701 e conhece os elóis, pessoas frágeis que vivem à superfície. Logo descobre os morlocks, que vivem no subsolo e dominam as máquinas. Ao perder a máquina, ele precisa recuperá-la para voltar, e depois segue para um futuro ainda mais distante.'),
('O Mágico de Oz', 'L. Frank Baum', 1900, 'Fantasia', 3,
 'Um tornado leva Dorothy e seu cachorro Totó para a terra de Oz, e ela segue pela estrada de tijolos amarelos para pedir ajuda ao Mágico.',
 'Dorothy quer voltar para o Kansas. No caminho, encontra o Espantalho, o Homem de Lata e o Leão Covarde, e cada um deseja algo do Mágico: cérebro, coração e coragem. Para conseguir a ajuda, o grupo precisa enfrentar a Bruxa Má do Oeste.'),
('O Cão dos Baskerville', 'Arthur Conan Doyle', 1902, 'Policial', 2,
 'Uma lenda sobre um cão sobrenatural assombra a família Baskerville, e Sherlock Holmes investiga a morte de sir Charles na charneca de Dartmoor.',
 'Watson acompanha o novo herdeiro, sir Henry, até a mansão em Dartmoor, enquanto Holmes parece ter ficado em Londres. Estranhos uivos e pegadas aumentam o medo. No fim, Holmes mostra que o cão não era sobrenatural e que um vizinho naturalista armou tudo para ficar com a herança.'),
('Percy Jackson e o Ladrão de Raios', 'Rick Riordan', 2005, 'Fantasia', 4,
 'Percy descobre que é filho de um deus grego, é acusado de roubar o raio de Zeus e viaja pelos Estados Unidos para evitar uma guerra entre os deuses.',
 'Aos doze anos, Percy vai parar no Acampamento Meio-Sangue e descobre sua origem. Acusado do roubo do raio, ele parte com Annabeth e Grover para recuperá-lo antes do solstício de verão. No caminho, enfrenta monstros e descobre que o verdadeiro culpado não é quem parece.'),
('Guia do Leitor Trata', 'Equipe Trata', 2026, 'Guia', 1,
 'Um guia curto e original que mostra como funciona o leitor do Trata: como ler, ajustar o texto e acrescentar novos livros.',
 'Três pequenos capítulos explicam o leitor: o que ele faz, como ajustar a letra e como o bibliotecário pode colocar novos textos no sistema.');

INSERT INTO livros (titulo, autor, ano, genero, quantidade, descricao, resumo)
SELECT s.titulo, s.autor, s.ano, s.genero, s.quantidade, s.descricao, s.resumo
FROM seed_livros s
WHERE NOT EXISTS (SELECT 1 FROM livros l WHERE l.titulo = s.titulo COLLATE utf8mb4_unicode_ci);

UPDATE livros l
JOIN seed_livros s ON s.titulo COLLATE utf8mb4_unicode_ci = l.titulo
SET l.descricao = COALESCE(NULLIF(l.descricao, ''), s.descricao),
    l.resumo    = COALESCE(NULLIF(l.resumo, ''), s.resumo);

DROP TEMPORARY TABLE IF EXISTS seed_livros;

-- ---------- Resumos dos livros que já estavam no acervo ----------
DROP TEMPORARY TABLE IF EXISTS seed_resumos;
CREATE TEMPORARY TABLE seed_resumos (titulo VARCHAR(150), resumo TEXT)
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO seed_resumos (titulo, resumo) VALUES
('Dom Casmurro', 'Bentinho cresce apaixonado por Capitu, a vizinha, enquanto a mãe prometeu que ele seria padre. Com a ajuda do amigo Escobar, ele escapa do seminário e se casa com ela. Anos depois, convencido de que Capitu o traiu com Escobar e de que o filho Ezequiel não é dele, rompe com a família. O livro nunca confirma se a suspeita era verdadeira.'),
('Memórias Póstumas de Brás Cubas', 'Brás Cubas, já morto, conta sua vida: o amor frustrado por Marcela, o caso com Virgília, casada com outro, e as ideias do amigo Quincas Borba. Ele morre ao tentar inventar um emplasto contra a melancolia e conclui que não teve filhos, e assim não passou adiante a miséria de ninguém.'),
('O Cortiço', 'João Romão, comerciante português, enriquece explorando Bertoleza e construindo um cortiço ao lado do sobrado do vizinho Miranda. Os moradores vivem entre festas, brigas e dificuldades, e o cortiço vai crescendo. Para ascender socialmente, João trai Bertoleza, que acaba se matando, e ele passa a ser respeitado.'),
('Capitães da Areia', 'Pedro Bala lidera os meninos que vivem num trapiche em Salvador. O grupo inclui o Professor, que desenha, o Sem-Pernas, Pirulito e Volta Seca. A chegada de Dora muda a vida de todos, e ela morre ainda jovem. No fim, Pedro Bala deixa o bando e passa a lutar pelos direitos dos trabalhadores.'),
('Vidas Secas', 'Fabiano, Sinhá Vitória, os dois meninos e a cachorra Baleia andam pelo sertão fugindo da seca e trabalham numa fazenda. Fabiano é preso injustamente pelo soldado amarelo. Baleia adoece e é sacrificada por Fabiano, e a família segue viagem rumo ao sul, com a esperança de uma vida melhor.'),
('A Hora da Estrela', 'Macabéa, alagoana que vive no Rio, é datilógrafa e leva uma vida de poucos desejos. Namora Olímpico de Jesus, que a troca por Glória, colega de trabalho. A cartomante Madama Carlota prevê um futuro feliz, e ao sair do consultório Macabéa é atropelada e morre.'),
('O Alienista', 'O doutor Bacamarte funda a Casa Verde e passa a internar cada vez mais moradores de Itaguaí, até que quase metade da cidade está presa. Uma revolta popular o obriga a mudar de método. Ao fim, ele conclui que o único realmente desequilibrado era ele mesmo e se interna.'),
('Iracema', 'Iracema, guardiã do segredo da jurema, se apaixona pelo guerreiro português Martim e quebra o voto de castidade. Os dois vivem juntos, e ela dá à luz Moacir, considerado o primeiro cearense. Quando Martim se afasta para guerrear, Iracema definha de tristeza e morre.'),
('O Pequeno Príncipe', 'Um piloto cai no deserto e conhece um menino que vive num asteroide com uma rosa. O príncipe conta que visitou outros planetas e conheceu adultos estranhos, e na Terra uma raposa lhe ensina o que é cativar. Ao final, ele volta para sua rosa com a ajuda de uma serpente.'),
('Alice no País das Maravilhas', 'Alice segue um coelho branco, cai numa toca e passa a mudar de tamanho. Conhece o Gato de Cheshire, o Chapeleiro Maluco e a Rainha de Copas, que manda cortar cabeças. Num julgamento absurdo, ela acorda e percebe que tudo foi um sonho.'),
('Harry Potter e a Pedra Filosofal', 'Harry mora com os tios, os Dursley, e recebe uma carta de Hogwarts. Na escola, faz amizade com Rony e Hermione e descobre que a pedra filosofal está guardada no castelo. Os três tentam impedir que ela seja roubada e descobrem que o vilão não é quem parecia.'),
('O Hobbit', 'Bilbo Bolseiro se junta a Gandalf e a treze anões para recuperar o tesouro tomado pelo dragão Smaug. Pelo caminho, encontra Gollum e seu anel mágico. Após a morte do dragão, uma grande batalha disputa o tesouro, e Bilbo volta para casa muito mudado.'),
('Reinações de Narizinho', 'Narizinho mora com a avó, Dona Benta, e a cozinheira Tia Nastácia. Um dia, ela visita o fundo do rio e conhece o Reino das Águas Claras e o príncipe Escamado. De volta ao sítio, faz a boneca de pano Emília falar, e juntas elas vivem aventuras.'),
('Meu Pé de Laranja Lima', 'Zezé é um menino pobre e esperto que apanha com frequência em casa. Seu melhor amigo é um pé de laranja lima, e ele conhece Portuga, que passa a tratá-lo com carinho. Quando Portuga morre num acidente de trem, Zezé fica muito triste, e a história mostra o fim da infância.'),
('1984', 'Winston trabalha no Ministério da Verdade, reescrevendo registros do passado. Ele começa um diário proibido e se envolve com Julia. Os dois acreditam fazer parte de uma resistência, mas são presos, torturados e reeducados, e Winston termina amando o Grande Irmão.'),
('A Revolução dos Bichos', 'Os animais expulsam o fazendeiro e criam sete mandamentos. Napoleão e Bola-de-Neve disputam o poder, e Napoleão vence, apoiado pelos cães. Aos poucos, as regras são mudadas pelos porcos, e no fim eles vivem como os antigos donos.'),
('Frankenstein', 'Victor cria uma criatura e foge dela. Rejeitada por todos, a criatura se vinga e mata pessoas próximas a Victor. Ele a persegue até o Ártico, onde conta sua história a um capitão e morre. A criatura promete desaparecer.'),
('Um Estudo em Vermelho', 'Watson e Holmes passam a dividir o apartamento da Baker Street. Quando um homem é encontrado morto, com a palavra RACHE escrita na parede, Holmes investiga e desmonta as pistas falsas. A segunda parte do livro mostra a motivação do assassino, que é vingança.'),
('Assassinato no Expresso do Oriente', 'O empresário Ratchett é encontrado morto, a facadas, num vagão preso na neve. Poirot interroga todos os passageiros e descobre que o crime está ligado ao sequestro de uma menina anos antes. A solução é que todos os passageiros do vagão participaram da vingança.'),
('O Diário de Anne Frank', 'Anne e a família se escondem num anexo secreto, junto de outras pessoas. Nos dois anos de reclusão, ela escreve sobre as brigas, a convivência, a amizade com Peter e seus sonhos de se tornar escritora. O grupo é descoberto em 1944, e Anne morre no campo de Bergen-Belsen. O pai, Otto, publica o diário.'),
('A Menina que Roubava Livros', 'Liesel é adotada por Hans e Rosa Hubermann e aprende a ler com o pai. Ela rouba livros e se torna amiga de Rudy. Quando a família esconde Max, um judeu, no porão, a vida muda. A guerra traz bombardeios, e Liesel sobrevive e guarda sua história.'),
('O Homem que Calculava', 'Beremiz Samir, calculista persa, viaja com o narrador e resolve problemas com a matemática. Entre eles, o famoso caso dos 35 camelos que três irmãos precisam dividir. Beremiz acaba chamando a atenção de importantes figuras do Oriente.'),
('Cosmos', 'O livro não tem enredo. Em cada capítulo, Sagan percorre um tema, da formação das estrelas à origem da vida, passando pela Biblioteca de Alexandria e por Kepler. Mostra como o conhecimento científico foi construído ao longo dos séculos.'),
('Orgulho e Preconceito', 'Os Bennet têm cinco filhas, e a mãe quer casá-las bem. Jane se apaixona por Bingley, enquanto Elizabeth julga mal o orgulhoso Darcy e se encanta com Wickham. Ao descobrir a verdade sobre os dois, Elizabeth muda de opinião, e Darcy a pede em casamento mais uma vez.'),
('A Ilha do Tesouro', 'Jim Hawkins encontra um mapa do tesouro na bagagem de um velho pirata. Ele embarca no Hispaniola com o doutor Livesey e o fidalgo Trelawney, e o cozinheiro Long John Silver revela ser o líder de um motim. Depois de muitas batalhas, o grupo encontra o tesouro, e Silver foge.');

UPDATE livros l
JOIN seed_resumos s ON s.titulo COLLATE utf8mb4_unicode_ci = l.titulo
SET l.resumo = s.resumo
WHERE l.resumo IS NULL OR l.resumo = '';

DROP TEMPORARY TABLE IF EXISTS seed_resumos;

-- ---------- Texto do "Guia do Leitor Trata" (conteúdo original, serve de exemplo do leitor) ----------
INSERT INTO capitulos (livro_id, numero, titulo, texto)
SELECT l.id, 1, 'Como ler no Trata',
'Este livro existe para mostrar como funciona o leitor do Trata. Você pode trocar o tamanho e o tipo da letra, a largura do texto e o tema claro ou escuro pelos controles no topo da página.

Seu progresso é salvo sozinho. Da próxima vez, o botão Continuar leitura leva você direto ao capítulo em que parou.'
FROM livros l
WHERE l.titulo = 'Guia do Leitor Trata'
  AND NOT EXISTS (SELECT 1 FROM capitulos c WHERE c.livro_id = l.id AND c.numero = 1);

INSERT INTO capitulos (livro_id, numero, titulo, texto)
SELECT l.id, 2, 'Ajustando o texto',
'Use A− e A+ para mudar o tamanho da letra. A fonte com serifa lembra um livro impresso. A fonte sem serifa costuma ser mais confortável em telas pequenas.

Para ler à noite, abra Configurações e escolha o tema escuro. Suas escolhas ficam guardadas neste aparelho.'
FROM livros l
WHERE l.titulo = 'Guia do Leitor Trata'
  AND NOT EXISTS (SELECT 1 FROM capitulos c WHERE c.livro_id = l.id AND c.numero = 2);

INSERT INTO capitulos (livro_id, numero, titulo, texto)
SELECT l.id, 3, 'Adicionando livros para ler',
'Quem é bibliotecário pode colocar o texto de um livro no leitor pela opção Gerenciar texto, na página do livro. Cole o texto ou envie um arquivo .txt e marque o começo de cada capítulo com uma linha iniciada por ##.

Use apenas obras de domínio público ou com autorização de quem detém os direitos. Bons lugares para encontrá-las são o portal Domínio Público, o Projeto Gutenberg e a Wikisource.'
FROM livros l
WHERE l.titulo = 'Guia do Leitor Trata'
  AND NOT EXISTS (SELECT 1 FROM capitulos c WHERE c.livro_id = l.id AND c.numero = 3);
