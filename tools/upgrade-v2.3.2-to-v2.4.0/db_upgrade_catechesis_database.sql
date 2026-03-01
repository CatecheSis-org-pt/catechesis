
BEGIN;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `familiar` ENGINE=InnoDB;
ALTER TABLE `casados` ENGINE=InnoDB;
ALTER TABLE `utilizador` ENGINE=InnoDB;
ALTER TABLE `registosLog` ENGINE=InnoDB;
ALTER TABLE `catequizando` ENGINE=InnoDB;
ALTER TABLE `baptismo` ENGINE=InnoDB;
ALTER TABLE `primeiraComunhao` ENGINE=InnoDB;
ALTER TABLE `profissaoFe` ENGINE=InnoDB;
ALTER TABLE `confirmacao` ENGINE=InnoDB;
ALTER TABLE `escolaridade` ENGINE=InnoDB;
ALTER TABLE `autorizacaoSaidaMenores` ENGINE=InnoDB;
ALTER TABLE `grupo` ENGINE=InnoDB;
ALTER TABLE `pertence` ENGINE=InnoDB;
ALTER TABLE `catequista` ENGINE=InnoDB;
ALTER TABLE `lecciona` ENGINE=InnoDB;
ALTER TABLE `inscreve` ENGINE=InnoDB;
ALTER TABLE `cod_postais_paroquia` ENGINE=InnoDB;
ALTER TABLE `configuracoes` ENGINE=InnoDB;
ALTER TABLE `catequese_virtual` ENGINE=InnoDB;
ALTER TABLE `catequese_virtual_lock` ENGINE=InnoDB;
ALTER TABLE `pedidoRenovacaoMatricula` ENGINE=InnoDB;
ALTER TABLE `pedidoInscricao` ENGINE=InnoDB;
ALTER TABLE `captcha_codes` ENGINE=InnoDB;

ALTER TABLE `grupo` MODIFY `ano_catecismo` TINYINT NOT NULL;
ALTER TABLE `grupo` MODIFY `turma` VARCHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `grupo` MODIFY `ano_lectivo` INT NOT NULL;
CREATE TABLE `sessao_catequese` (
                                    `data` DATE NOT NULL,
                                    `ano_catecismo` TINYINT NOT NULL,
                                    `turma` VARCHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                                    `ano_lectivo` INT NOT NULL,
                                    PRIMARY KEY (`data`, `ano_catecismo`, `turma`, `ano_lectivo`),
                                    KEY `ano_catecismo` (`ano_catecismo`, `turma`, `ano_lectivo`),
                                    FOREIGN KEY (`ano_catecismo`, `turma`, `ano_lectivo`) REFERENCES `grupo` (`ano_catecismo`, `turma`, `ano_lectivo`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE TABLE `presenca` (
                            `data` DATE NOT NULL,
                            `ano_catecismo` TINYINT NOT NULL,
                            `turma` VARCHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                            `ano_lectivo` INT NOT NULL,
                            `cid` INT NOT NULL,
                            `presenca` TINYINT DEFAULT NULL,
                            `marcada_por` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                            PRIMARY KEY (`data`, `ano_catecismo`, `turma`, `ano_lectivo`, `cid`),
                            KEY `data` (`data`, `ano_catecismo`, `turma`, `ano_lectivo`),
                            FOREIGN KEY (`data`, `ano_catecismo`, `turma`, `ano_lectivo`) REFERENCES `sessao_catequese` (`data`, `ano_catecismo`, `turma`, `ano_lectivo`),
                            FOREIGN KEY (`cid`) REFERENCES `catequizando` (`cid`),
                            FOREIGN KEY (`marcada_por`) REFERENCES `utilizador` (`username`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `grupo` ADD COLUMN `dia_da_semana` TINYINT DEFAULT NULL AFTER `ano_lectivo`;
ALTER TABLE `grupo` ADD COLUMN `hora_inicio` TIME DEFAULT NULL AFTER `dia_da_semana`;
ALTER TABLE `grupo` ADD COLUMN `hora_fim` TIME DEFAULT NULL AFTER `hora_inicio`;
ALTER TABLE `utilizador` MODIFY `tel` INT DEFAULT NULL;
ALTER TABLE `utilizador` ADD COLUMN `ultima_versao_vista` VARCHAR (20) DEFAULT NULL AFTER `email`;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;