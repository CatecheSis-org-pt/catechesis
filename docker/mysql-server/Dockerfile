FROM mysql:5.7

# Scripts are run by MySQL on first startup in alphabetical order, so we prepend A_ B_ C_ ...
COPY database/catechesis_database.sql /docker-entrypoint-initdb.d/A_catechesis_database.sql
COPY database/ulogin_database.sql /docker-entrypoint-initdb.d/B_ulogin_database.sql
COPY database/script_collation.sql /docker-entrypoint-initdb.d/C_script_collation.sql
COPY database/users.docker.sql /docker-entrypoint-initdb.d/D_users.sql

WORKDIR /home
#RUN service mysql restart