#desativa a verificacao das chaves estrangeiras
SET foreign_key_checks = 0 ;

DROP TABLE IF EXISTS familiar;
CREATE TABLE familiar(
	fid		        INT AUTO_INCREMENT,
	nome		    VARCHAR(255),
	prof		    VARCHAR(80),
	morada		    VARCHAR(320),
	cod_postal	    VARCHAR(50),
	telefone	    VARCHAR(20),
	telemovel	    VARCHAR(20),
	email		    VARCHAR(320),
	RGPD_assinado   TINYINT,        #0=nao, 1=sim
	
	PRIMARY KEY (fid)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;
	
	
DROP TABLE IF EXISTS casados;
CREATE TABLE casados(
	fid1		INT,
	fid2		INT,
	como		VARCHAR(20) NOT NULL,
	
	PRIMARY KEY (fid1, fid2),
	FOREIGN KEY (fid1) REFERENCES familiar(fid) ON DELETE CASCADE,
	FOREIGN KEY (fid2) REFERENCES familiar(fid) ON DELETE CASCADE
)
CHARACTER SET utf8 COLLATE utf8_general_ci;
	

DROP TABLE IF EXISTS utilizador;
CREATE TABLE utilizador(
	username	VARCHAR(50),
	nome		VARCHAR(255) NOT NULL,
	admin		TINYINT NOT NULL,		# 0=nao, 1=sim
	estado		TINYINT NOT NULL,		# 0=inactivo, 1=activo
	tel		INT,
	email		VARCHAR(255),
	
	PRIMARY KEY (username)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS registosLog;
CREATE TABLE registosLog(
	LSN			INT AUTO_INCREMENT,
	data_hora		DATETIME NOT NULL,
	username		VARCHAR(20) NOT NULL,
	accao			VARCHAR(255) NOT NULL,
	
	PRIMARY KEY (LSN),
	FOREIGN KEY (username) REFERENCES utilizador(username)
	
)
CHARACTER SET utf8 COLLATE utf8_general_ci;

	

DROP TABLE IF EXISTS catequizando;
CREATE TABLE catequizando(
	cid		                    INT AUTO_INCREMENT,
	nome		                VARCHAR(255) NOT NULL,
	data_nasc	                DATE NOT NULL,
    nif                         INT,
	local_nasc	                VARCHAR(40) NOT NULL,
	num_irmaos	                INT NOT NULL,
	escuteiro	                TINYINT NOT NULL,		#inteiro de 1 byte. 0=false, !0=true
	autorizou_fotos	            TINYINT NOT NULL,
	autorizou_saida_sozinho     TINYINT,
	pai		                    INT,
	mae		                    INT,
	enc_edu		                INT NOT NULL,
	enc_edu_quem	            VARCHAR(50),
	foto		                TEXT,
	obs		                    LONGTEXT,
	criado_por	                VARCHAR(20) NOT NULL,
	criado_em	                DATETIME NOT NULL,
	lastLSN_ficha	            INT NULL,
	lastLSN_arquivo	            INT NULL,
	lastLSN_autorizacoes        INT NULL,
	
	PRIMARY KEY (cid),
    UNIQUE(nif),
	FOREIGN KEY (pai) REFERENCES familiar(fid),
	FOREIGN KEY (mae) REFERENCES familiar(fid),
	FOREIGN KEY (enc_edu) REFERENCES familiar(fid),
	FOREIGN KEY (criado_por) REFERENCES utilizador(username),
	FOREIGN KEY (lastLSN_ficha) REFERENCES registosLog(LSN) ON DELETE SET NULL,
	FOREIGN KEY (lastLSN_arquivo) REFERENCES registosLog(LSN) ON DELETE SET NULL,
    FOREIGN KEY (lastLSN_autorizacoes) REFERENCES registosLog(LSN) ON DELETE SET NULL
)
CHARACTER SET utf8 COLLATE utf8_general_ci;
	
	
	
DROP TABLE IF EXISTS baptismo;
CREATE TABLE baptismo(
	cid		INT,
	data		DATE NOT NULL,
	paroquia	VARCHAR(50) NOT NULL,
	comprovativo	VARCHAR(255),
	
	PRIMARY KEY (cid),
	FOREIGN KEY (cid) REFERENCES catequizando(cid)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;



DROP TABLE IF EXISTS primeiraComunhao;
CREATE TABLE primeiraComunhao(
	cid		INT,
	data		DATE NOT NULL,
	paroquia	VARCHAR(50) NOT NULL,
	comprovativo	VARCHAR(255),
	
	PRIMARY KEY (cid),
	FOREIGN KEY (cid) REFERENCES catequizando(cid)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS profissaoFe;
CREATE TABLE profissaoFe(
	cid		INT,
	data		DATE NOT NULL,
	paroquia	VARCHAR(50) NOT NULL,
	comprovativo	VARCHAR(255),
	
	PRIMARY KEY (cid),
	FOREIGN KEY (cid) REFERENCES catequizando(cid)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS confirmacao;
CREATE TABLE confirmacao(
	cid		INT,
	data		DATE NOT NULL,
	paroquia	VARCHAR(50) NOT NULL,
	comprovativo	VARCHAR(255),
	
	PRIMARY KEY (cid),
	FOREIGN KEY (cid) REFERENCES catequizando(cid)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;



DROP TABLE IF EXISTS escolaridade;
CREATE TABLE escolaridade(
	cid			INT,
	ano_lectivo		INT,		#guardado como uma um inteiro '20142015' por exemplo
	ano_escolaridade	VARCHAR(20) NOT NULL,
	
	PRIMARY KEY (cid, ano_lectivo),
	FOREIGN KEY (cid) REFERENCES catequizando(cid)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS autorizacaoSaidaMenores;
CREATE TABLE autorizacaoSaidaMenores(
	cid			    INT,
	fid             INT,
	parentesco		VARCHAR(50),

	PRIMARY KEY (cid, fid),
	FOREIGN KEY (cid) REFERENCES catequizando(cid) ON DELETE CASCADE,
	FOREIGN KEY (fid) REFERENCES familiar(fid) ON DELETE CASCADE
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS grupo;
CREATE TABLE grupo(
	ano_catecismo		TINYINT,
	turma			VARCHAR(1),
	ano_lectivo		INT,		#guardado como uma um inteiro '20142015' por exemplo
	#atributos catecismo, missa, catequese...?
	
	PRIMARY KEY (ano_catecismo, turma, ano_lectivo)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;
	

DROP TABLE IF EXISTS pertence;
CREATE TABLE pertence(
	cid			    INT,
	ano_catecismo	TINYINT,
	turma			VARCHAR(1),
	ano_lectivo		INT,		#guardado como uma um inteiro '20142015' por exemplo
	passa			TINYINT,	# NULL|-1=chumba, 1=passa
	
	PRIMARY KEY (cid, ano_catecismo, turma, ano_lectivo),
	FOREIGN KEY (ano_catecismo, turma, ano_lectivo) REFERENCES grupo(ano_catecismo, turma, ano_lectivo),
	FOREIGN KEY (cid) REFERENCES catequizando(cid)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;	
	
	



DROP TABLE IF EXISTS catequista;
CREATE TABLE catequista(
	username	VARCHAR(20),
	estado		TINYINT NOT NULL,		# 0=inactivo, 1=activo
		
	PRIMARY KEY (username),
	FOREIGN KEY (username) REFERENCES utilizador(username)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS lecciona;
CREATE TABLE lecciona(
	username	VARCHAR(20) NOT NULL,
	ano_catecismo	TINYINT,
	turma		VARCHAR(1),
	ano_lectivo	INT,
	
	PRIMARY KEY (username, ano_catecismo, turma, ano_lectivo),
	FOREIGN KEY (ano_catecismo, turma, ano_lectivo) REFERENCES grupo(ano_catecismo, turma, ano_lectivo),
	FOREIGN KEY (username) REFERENCES catequista(username)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS inscreve;
CREATE TABLE inscreve(
	username	VARCHAR(20) NOT NULL,
	cid		INT,
	ano_catecismo	TINYINT,
	turma		VARCHAR(1),
	ano_lectivo	INT,
	pago		TINYINT NOT NULL,       #0=Nao pagou; 1=Pagou
	
	PRIMARY KEY (cid, ano_catecismo, turma, ano_lectivo),
	FOREIGN KEY (cid, ano_catecismo, turma, ano_lectivo) REFERENCES pertence(cid, ano_catecismo, turma, ano_lectivo),
	FOREIGN KEY (username) REFERENCES utilizador(username)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS cod_postais_paroquia;
CREATE TABLE cod_postais_paroquia(
	codigo		VARCHAR(10),
	
	PRIMARY KEY (codigo)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS configuracoes;
CREATE TABLE configuracoes(
	chave		VARCHAR(255),
	valor		TEXT,
	
	PRIMARY KEY (chave)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS catequese_virtual;
CREATE TABLE catequese_virtual(
	data						    DATE NOT NULL,
	ano_catecismo		            TINYINT NOT NULL,              # -1 allows a default session for all catechisms
    turma                           VARCHAR(1) DEFAULT '',         # '' allows a catechism session not tied to a particular group (compatibility with previous version)
	conteudo				        LONGTEXT,
	ultima_modificacao_user         VARCHAR(50) NULL,
	ultima_modificacao_timestamp    DATETIME NULL,
	
	PRIMARY KEY (data, ano_catecismo, turma),
	FOREIGN KEY(ultima_modificacao_user) REFERENCES utilizador(username) ON DELETE SET NULL
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS catequese_virtual_lock;
CREATE TABLE catequese_virtual_lock
(
    data           DATE    NOT NULL,
    ano_catecismo  TINYINT NOT NULL,
    turma          VARCHAR(1) DEFAULT '',         # '' allows a catechism session not tied to a particular group (compatibility with previous version)
    lock_user      VARCHAR(50) NOT NULL,
    lock_timestamp DATETIME NOT NULL,

    PRIMARY KEY (data, ano_catecismo, turma, lock_user),
    FOREIGN KEY (lock_user) REFERENCES utilizador(username) ON DELETE CASCADE
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


DROP TABLE IF EXISTS pedidoRenovacaoMatricula;
CREATE TABLE pedidoRenovacaoMatricula
(
    rid                     INT AUTO_INCREMENT,
    data_hora               DATETIME NOT NULL,
    endereco_ip             VARCHAR(45) NOT NULL,
    enc_edu_nome            VARCHAR(255) NOT NULL,
    enc_edu_tel	            VARCHAR(20) NOT NULL,
    enc_edu_email           VARCHAR(320),
    catequizando_nome       VARCHAR(255) NOT NULL,
    ultimo_catecismo        TINYINT NOT NULL,
    observacoes             LONGTEXT,
    processado              TINYINT NOT NULL,           # 0 = pedido pendente ;  1 = pedido ja processado
    ano_lectivo_inscricao	INT NULL,		            # Ano lectivo em que o catequiazando foi inscrito (depois de processado), guardado como uma um inteiro '20142015' por exemplo
    ano_catecismo_inscricao	TINYINT NULL,               # Catecismo onde o catequizando foi inscrito (depois de processado)
    turma_inscricao			VARCHAR(1) NULL,            # Turma onde o catequizando for inscrito (depois de processado)

    PRIMARY KEY (rid)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;



DROP TABLE IF EXISTS pedidoInscricao;
CREATE TABLE pedidoInscricao
(
    iid                         INT AUTO_INCREMENT,
    data_hora                   DATETIME NOT NULL,
    endereco_ip                 VARCHAR(45) NOT NULL,

    #catequizando fields
    nome		                VARCHAR(255) NOT NULL,
    data_nasc	                DATE NOT NULL,
    local_nasc	                VARCHAR(40) NOT NULL,
    nif                         INT,
    num_irmaos	                INT NOT NULL,
    escuteiro	                TINYINT NOT NULL,		#inteiro de 1 byte. 0=false, !0=true
    autorizou_fotos	            TINYINT NOT NULL,
    autorizou_saida_sozinho     TINYINT NOT NULL,
    enc_edu		                INT NOT NULL,           # 0=pai, 1=mae, 2=outro
    foto		                TEXT,
    obs		                    LONGTEXT,

    #familiar fields
    pai_nome                    VARCHAR(255),
    prof_pai		            VARCHAR(80),
    mae_nome                    VARCHAR(255),
    prof_mae		            VARCHAR(80),
    enc_edu_parentesco          VARCHAR(50) NULL,       # Preechido apenas se enc_edu=2
    enc_edu_nome                VARCHAR(255) NULL,      # Preenchido apenas se enc. edu. nao for pai nem mae
    prof_enc_edu                VARCHAR(80),            # Preenchido apenas se enc. edu. nao for pai nem mae
    casados_como		        VARCHAR(20),
    morada		                VARCHAR(320),
    cod_postal	                VARCHAR(50),
    telefone	                VARCHAR(20),
    telemovel	                VARCHAR(20),
    email		                VARCHAR(320),

    #sacramento fields
    data_baptismo		        DATE NULL,
    paroquia_baptismo	        VARCHAR(50) NULL,
    data_comunhao		        DATE NULL,
    paroquia_comunhao	        VARCHAR(50) NULL,

    #autorizacaoSaidaMenores
    autorizacoesSaidaMenores    LONGTEXT,               # Os varios familiares, parentescos e telefones concatenados

    #outros fields
    ultimo_catecismo            INT NULL,               # Ultimo catecismo frequentado
    cid                         INT NULL,               # Quando o pedido for processado, preencher aqui cid do catequizando


    PRIMARY KEY (iid)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


# Table for secureimage to store CAPTCHAs
DROP TABLE IF EXISTS captcha_codes;
CREATE TABLE captcha_codes
(
    id                  VARCHAR(40) NOT NULL,
    namespace           VARCHAR(32) NOT NULL,
    code                VARCHAR(32) NOT NULL,
    code_display        VARCHAR(32) NOT NULL,
    created             INT NOT NULL,
    audio_data          MEDIUMBLOB NULL,

    PRIMARY KEY(id, namespace),
    INDEX(created)
)
CHARACTER SET utf8 COLLATE utf8_general_ci;


#ativa a verificacao das chaves estrangeiras
SET foreign_key_checks = 1 ;
