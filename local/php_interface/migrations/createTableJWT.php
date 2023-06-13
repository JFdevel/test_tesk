<?php

namespace Sprint\Migration;


class createTableJWT extends Version
{

    protected $description = "Создание таблицы для refresh токенов";

    public function up()
    {
        global $DB;
        $DB->Query('
            CREATE TABLE refresh_tokens (
            ID INT NOT NULL AUTO_INCREMENT,
            USER_ID INT NOT NULL,
            TOKEN VARCHAR(255) NOT NULL,
            CREATED_AT DATETIME DEFAULT CURRENT_TIMESTAMP,
            EXPIRES_AT DATETIME NOT NULL,
            PRIMARY KEY(ID));
        ');
    }

    public function down()
    {
        global $DB;
        $DB->Query('DROP TABLE IF EXISTS `refresh_tokens`');
    }

}
