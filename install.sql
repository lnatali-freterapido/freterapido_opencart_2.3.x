-- Cria a tabela que relaciona as categorias do OpenCart com as do Frete Rápido

CREATE TABLE IF NOT EXISTS `oc_category_to_fr_category`
(
    category_id INT(11) NOT NULL,
    fr_category_id INT(11) NOT NULL,
    CONSTRAINT `PRIMARY` PRIMARY KEY (category_id, fr_category_id)
);


-- Cria a tabela de categorias do Frete Rápido

CREATE TABLE IF NOT EXISTS fr_category
(
  fr_category_id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
  name           VARCHAR(255)        NOT NULL,
  code           SMALLINT(6)         NOT NULL
);

-- Limpa os registros da tabela de categorias

TRUNCATE TABLE fr_category;

-- Insere novamente as categorias do Frete Rápido

INSERT INTO fr_category
(fr_category_id, name, code) VALUES
  (1, 'Abrasivos', 1),
  (2, 'Adubos / Fertilizantes', 2),
  (3, 'Alimentos perecíveis', 3),
  (4, 'Artigos para Pesca', 4),
  (5, 'Auto Peças', 5),
  (6, 'Bebidas / Destilados', 6),
  (7, 'Brindes', 7),
  (8, 'Brinquedos', 8),
  (9, 'Calçados', 9),
  (10, 'CD / DVD / Blu-Ray', 10),
  (11, 'Combustíveis / Óleos', 11),
  (12, 'Confecção', 12),
  (13, 'Cosméticos', 13),
  (14, 'Couro', 14),
  (15, 'Derivados Petróleo', 15),
  (16, 'Descartáveis', 16),
  (17, 'Editorial', 17),
  (18, 'Eletrônicos', 18),
  (19, 'Eletrodomésticos', 19),
  (20, 'Embalagens', 20),
  (21, 'Explosivos / Pirotécnicos', 21),
  (22, 'Medicamentos', 22),
  (23, 'Ferragens', 23),
  (24, 'Ferramentas', 24),
  (25, 'Fibras Ópticas', 25),
  (26, 'Fonográfico', 26),
  (27, 'Fotográfico', 27),
  (28, 'Fraldas / Geriátricas', 28),
  (29, 'Higiene / Limpeza', 29),
  (30, 'Impressos', 30),
  (31, 'Informática / Computadores', 31),
  (32, 'Instrumento Musical', 32),
  (33, 'Livro(s)', 33),
  (34, 'Materiais Escolares', 34),
  (35, 'Materiais Esportivos', 35),
  (36, 'Materiais Frágeis', 36),
  (37, 'Material de Construção', 37),
  (38, 'Material de Irrigação', 38),
  (39, 'Material Elétrico / Lâmpada(s)', 39),
  (40, 'Material Gráfico', 40),
  (41, 'Material Hospitalar', 41),
  (42, 'Material Odontológico', 42),
  (43, 'Material Pet Shop', 43),
  (44, 'Material Veterinário', 44),
  (45, 'Móveis montados', 45),
  (46, 'Moto Peças', 46),
  (47, 'Mudas / Plantas', 47),
  (48, 'Papelaria / Documentos', 48),
  (49, 'Perfumaria', 49),
  (50, 'Material Plástico', 50),
  (51, 'Pneus e Borracharia', 51),
  (52, 'Produtos Cerâmicos', 52),
  (53, 'Produtos Químicos', 53),
  (54, 'Produtos Veterinários', 54),
  (55, 'Revistas', 55),
  (56, 'Sementes', 56),
  (57, 'Suprimentos Agrícolas / Rurais', 57),
  (58, 'Têxtil', 58),
  (59, 'Vacinas', 59),
  (60, 'Vestuário', 60),
  (61, 'Vidros / Frágil', 61),
  (62, 'Cargas refrigeradas/congeladas', 62),
  (63, 'Papelão', 63),
  (64, 'Móveis desmontados', 64),
  (65, 'Sofá', 65),
  (66, 'Colchão', 66),
  (67, 'Travesseiro', 67),
  (68, 'Móveis com peças de vidro', 68),
  (69, 'Acessórios de Airsoft / Paintball', 69),
  (70, 'Acessórios de Pesca', 70),
  (71, 'Simulacro de Arma / Airsoft', 71),
  (72, 'Arquearia', 72),
  (73, 'Acessórios de Arquearia', 73),
  (74, 'Alimentos não perecíveis', 74),
  (75, 'Caixa de embalagem', 75),
  (76, 'TV / Monitores', 76),
  (77, 'Linha Branca', 77),
  (78, 'Vitaminas / Suplementos nutricionais', 78),
  (79, 'Malas / Mochilas', 79),
  (80, 'Máquina / Equipamentos', 80),
  (81, 'Rações / Alimento para Animal', 81),
  (82, 'Artigos para Camping)', 82),
  (83, 'Outros', 999);

-- Cria a tabela para inserir metadata dos fretes

CREATE TABLE IF NOT EXISTS `oc_order_meta`
(
  meta_id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
  order_id INT(11) NOT NULL,
  meta_key VARCHAR(255),
  meta_value LONGTEXT
);
