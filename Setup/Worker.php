<?php

namespace Plugin\FormSubmissions\Setup;

class Worker extends \Ip\SetupWorker
{
    public function activate()
    {
        if (ipDb()->isSQLite()) {
            $id = '`id` INTEGER PRIMARY KEY AUTOINCREMENT';
            $tableEnd = '';
        } else {
            $id = '`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY';
            $tableEnd = ' DEFAULT CHARSET=utf8mb4';
        }

        // `fields` is JSON: [{"label": ..., "value": ..., "type": "Text|Email|File|..."}]; files carry "files": [{"name", "stored", "size"}].
        ipDb()->execute('CREATE TABLE IF NOT EXISTS ' . ipTable('formsubmissions_entry') . " (
            $id,
            `widgetId` int(11) NOT NULL,
            `pageId` int(11) DEFAULT NULL,
            `pageTitle` varchar(255) DEFAULT NULL,
            `languageCode` varchar(10) DEFAULT NULL,
            `fields` mediumtext NOT NULL,
            `senderEmail` varchar(255) DEFAULT NULL,
            `ip` varchar(45) DEFAULT NULL,
            `fileDir` varchar(64) DEFAULT NULL,
            `createdAt` int(11) NOT NULL,
            `readAt` int(11) DEFAULT NULL
        )" . $tableEnd);
    }

    public function remove()
    {
        \Plugin\FormSubmissions\Model::deleteAllFiles();
        ipDb()->execute('DROP TABLE IF EXISTS ' . ipTable('formsubmissions_entry'));
    }
}
