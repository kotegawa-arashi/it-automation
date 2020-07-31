ALTER TABLE F_CREATE_MENU_INFO MODIFY COLUMN MENU_NAME VARCHAR(256);

ALTER TABLE F_CREATE_MENU_INFO_JNL MODIFY COLUMN MENU_NAME VARCHAR(256);


ALTER TABLE F_OTHER_MENU_LINK MODIFY COLUMN COLUMN_DISP_NAME VARCHAR(4096);

ALTER TABLE F_OTHER_MENU_LINK_JNL MODIFY COLUMN COLUMN_DISP_NAME VARCHAR(4096);


UPDATE A_MENU_LIST SET MENU_NAME='Menu definition information',DISP_SEQ=2 WHERE MENU_ID=2100160001;
UPDATE A_MENU_LIST SET DISP_SEQ=4 WHERE MENU_ID=2100160002;
UPDATE A_MENU_LIST SET DISP_SEQ=6 WHERE MENU_ID=2100160003;
UPDATE A_MENU_LIST SET MENU_NAME='Menu creation history',DISP_SEQ=7 WHERE MENU_ID=2100160004;
UPDATE A_MENU_LIST SET DISP_SEQ=3 WHERE MENU_ID=2100160008;
UPDATE A_MENU_LIST SET DISP_SEQ=5 WHERE MENU_ID=2100160009;

INSERT INTO A_MENU_LIST (MENU_ID,MENU_GROUP_ID,MENU_NAME,WEB_PRINT_LIMIT,WEB_PRINT_CONFIRM,XLS_PRINT_LIMIT,LOGIN_NECESSITY,SERVICE_STATUS,AUTOFILTER_FLG,INITIAL_FILTER_FLG,DISP_SEQ,NOTE,DISUSE_FLAG,LAST_UPDATE_TIMESTAMP,LAST_UPDATE_USER) VALUES(2100160011,2100011601,'Create/Define menu',NULL,NULL,NULL,1,0,1,2,1,NULL,'0',STR_TO_DATE('2015/04/01 10:00:00.000000','%Y/%m/%d %H:%i:%s.%f'),1);
INSERT INTO A_MENU_LIST_JNL (JOURNAL_SEQ_NO,JOURNAL_REG_DATETIME,JOURNAL_ACTION_CLASS,MENU_ID,MENU_GROUP_ID,MENU_NAME,WEB_PRINT_LIMIT,WEB_PRINT_CONFIRM,XLS_PRINT_LIMIT,LOGIN_NECESSITY,SERVICE_STATUS,AUTOFILTER_FLG,INITIAL_FILTER_FLG,DISP_SEQ,NOTE,DISUSE_FLAG,LAST_UPDATE_TIMESTAMP,LAST_UPDATE_USER) VALUES(-160011,STR_TO_DATE('2015/04/01 10:00:00.000000','%Y/%m/%d %H:%i:%s.%f'),'INSERT',2100160011,2100011601,'Create/Define menu',NULL,NULL,NULL,1,0,1,2,1,NULL,'0',STR_TO_DATE('2015/04/01 10:00:00.000000','%Y/%m/%d %H:%i:%s.%f'),1);


INSERT INTO A_ROLE_MENU_LINK_LIST (LINK_ID,ROLE_ID,MENU_ID,PRIVILEGE,NOTE,DISUSE_FLAG,LAST_UPDATE_TIMESTAMP,LAST_UPDATE_USER) VALUES(2100160011,1,2100160011,1,'System Administrator','0',STR_TO_DATE('2015/04/01 10:00:00.000000','%Y/%m/%d %H:%i:%s.%f'),1);
INSERT INTO A_ROLE_MENU_LINK_LIST_JNL (JOURNAL_SEQ_NO,JOURNAL_REG_DATETIME,JOURNAL_ACTION_CLASS,LINK_ID,ROLE_ID,MENU_ID,PRIVILEGE,NOTE,DISUSE_FLAG,LAST_UPDATE_TIMESTAMP,LAST_UPDATE_USER) VALUES(-160011,STR_TO_DATE('2015/04/01 10:00:00.000000','%Y/%m/%d %H:%i:%s.%f'),'INSERT',2100160011,1,2100160011,1,'System Administrator','0',STR_TO_DATE('2015/04/01 10:00:00.000000','%Y/%m/%d %H:%i:%s.%f'),1);

