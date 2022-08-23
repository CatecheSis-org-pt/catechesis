DELIMITER ;

delete from cod_postais_paroquia;

insert into cod_postais_paroquia select distinct SUBSTRING(cod_postal,1,8) from familiar where cod_postal LIKE "%Laranjeiro" or cod_postal like "%Almada" or cod_postal like "%Cova da Piedade";
