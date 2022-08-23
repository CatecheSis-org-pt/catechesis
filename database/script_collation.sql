#desativa a verificacao das chaves estrangeiras
SET foreign_key_checks = 0 ;

alter table baptismo convert to character set utf8 collate utf8_general_ci;
alter table casados convert to character set utf8 collate utf8_general_ci;
alter table catequista convert to character set utf8 collate utf8_general_ci;
alter table catequizando convert to character set utf8 collate utf8_general_ci;
alter table confirmacao convert to character set utf8 collate utf8_general_ci;
alter table escolaridade convert to character set utf8 collate utf8_general_ci;
alter table familiar convert to character set utf8 collate utf8_general_ci;
alter table grupo convert to character set utf8 collate utf8_general_ci;
alter table inscreve convert to character set utf8 collate utf8_general_ci;
alter table lecciona convert to character set utf8 collate utf8_general_ci;
alter table pertence convert to character set utf8 collate utf8_general_ci;
alter table primeiraComunhao convert to character set utf8 collate utf8_general_ci;
alter table profissaoFe convert to character set utf8 collate utf8_general_ci;
alter table registosLog convert to character set utf8 collate utf8_general_ci;
alter table ul_blocked_ips convert to character set utf8 collate utf8_general_ci;
alter table ul_log convert to character set utf8 collate utf8_general_ci;
alter table ul_logins convert to character set utf8 collate utf8_general_ci;
alter table ul_nonces convert to character set utf8 collate utf8_general_ci;
alter table ul_sessions convert to character set utf8 collate utf8_general_ci;
alter table utilizador convert to character set utf8 collate utf8_general_ci;
alter table cod_postais_paroquia convert to character set utf8 collate utf8_general_ci;
alter table configuracoes convert to character set utf8 collate utf8_general_ci;
alter table catequese_virtual convert to character set utf8 collate utf8_general_ci;
alter table catequese_virtual_lock convert to character set utf8 collate utf8_general_ci;
alter table salaCatequeseVirtual convert to character set utf8 collate utf8_general_ci;

#ativa a verificacao das chaves estrangeiras
SET foreign_key_checks = 1 ;