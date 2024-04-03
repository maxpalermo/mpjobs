truncate table {DB_PREFIX}mp_job_area;
insert into {DB_PREFIX}mp_job_area (id_job_area, date_add) select distinct id_job_area,NOW() from {DB_PREFIX}job_area;
truncate table {DB_PREFIX}mp_job_area_lang;
insert into {DB_PREFIX}mp_job_area_lang (id_job_area, id_lang, name) select id_job_area,id_lang,upper(name) from {DB_PREFIX}job_area;

truncate table {DB_PREFIX}mp_job_name;
insert into {DB_PREFIX}mp_job_name (id_job_name, date_add) select distinct id_job_name,NOW() from {DB_PREFIX}job_name;
truncate table {DB_PREFIX}mp_job_name_lang;
insert into {DB_PREFIX}mp_job_name_lang (id_job_name, id_lang, name) select id_job_name,id_lang,upper(name) from {DB_PREFIX}job_name;

truncate table {DB_PREFIX}mp_job_link;
insert into {DB_PREFIX}mp_job_link (id_job_area, id_job_name, date_add) select id_job_area,id_job_name,NOW() from {DB_PREFIX}job_link;