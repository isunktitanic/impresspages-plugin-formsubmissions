<?php

namespace Plugin\FormSubmissions;

class Model
{
    const FILE_ROOT = 'file/secure/formsubmissions/';

    /**
     * Saves a successful Form widget submission.
     *
     * @return int|null the new entry's id
     */
    public static function store($widgetId, array $postData)
    {
        $widgetRecord = \Ip\Internal\Content\Model::getWidgetRecord($widgetId);
        if (!$widgetRecord || $widgetRecord['name'] !== 'Form') {
            return null;
        }

        $reader = new FormReader();
        $form = $reader->buildForm($widgetId, $widgetRecord['data']);

        $fields = array();
        $senderEmail = null;
        $files = array();
        foreach ($form->getFields() as $field) {
            if ($field->getType() != \Ip\Form\Field::TYPE_REGULAR) {
                continue;
            }
            $name = $field->getName();
            $class = get_class($field);
            $type = substr($class, strrpos($class, '\\') + 1);
            $item = array(
                'label' => (string)$field->getLabel(),
                'type' => $type,
                'value' => (string)$field->getValueAsString($postData, $name),
            );

            if ($field instanceof \Ip\Form\Field\Email && $item['value'] !== '') {
                $senderEmail = mb_substr($item['value'], 0, 255);
            }
            if ($field instanceof \Ip\Form\Field\File) {
                $paths = $field->getFiles($postData, $name);
                $names = $field->originalFileNames($postData, $name);
                $item['files'] = array();
                foreach ($paths as $key => $path) {
                    $files[] = array('field' => count($fields), 'path' => $path, 'name' => $names[$key] ?? basename($path));
                }
            }
            $fields[] = $item;
        }

        $pageId = null;
        $pageTitle = null;
        $languageCode = null;
        if (!empty($widgetRecord['revisionId'])) {
            $revision = \Ip\Internal\Revision::getRevision($widgetRecord['revisionId']);
            if (!empty($revision['pageId'])) {
                $page = ipContent()->getPage($revision['pageId']);
                if ($page) {
                    $pageId = (int)$revision['pageId'];
                    $pageTitle = mb_substr((string)$page->getTitle(), 0, 255);
                    $languageCode = $page->getLanguageCode();
                }
            }
        }

        $fileDir = null;
        if ($files && ipGetOption('FormSubmissions.keepFiles', 1)) {
            $fileDir = bin2hex(random_bytes(16)); // unguessable, in case the host ignores .htaccess
            $dir = self::fileDir($fileDir);
            if (self::prepareDir($dir)) {
                foreach ($files as $file) {
                    $stored = bin2hex(random_bytes(8)) . '-' . self::safeName($file['name']);
                    if (@copy($file['path'], $dir . $stored)) {
                        $fields[$file['field']]['files'][] = array(
                            'name' => $file['name'],
                            'stored' => $stored,
                            'size' => (int)@filesize($dir . $stored),
                        );
                    }
                }
            } else {
                $fileDir = null;
            }
        }

        $id = ipDb()->insert('formsubmissions_entry', array(
            'widgetId' => (int)$widgetId,
            'pageId' => $pageId,
            'pageTitle' => $pageTitle,
            'languageCode' => $languageCode,
            'fields' => json_encode($fields, JSON_UNESCAPED_UNICODE),
            'senderEmail' => $senderEmail,
            'ip' => ipGetOption('FormSubmissions.storeIp', 0) ? mb_substr((string)ipRequest()->getServer('REMOTE_ADDR'), 0, 45) : null,
            'fileDir' => $fileDir,
            'createdAt' => time(),
        ));

        return $id ? (int)$id : null;
    }

    public static function safeName($name)
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$name);
        $name = trim($name, '._');
        return $name !== '' ? mb_substr($name, 0, 100) : 'file';
    }

    public static function fileDir($fileDir)
    {
        return ipFile(self::FILE_ROOT . $fileDir . '/');
    }

    protected static function prepareDir($dir)
    {
        $root = ipFile(self::FILE_ROOT);
        if (!is_dir($root) && !@mkdir($root, 0755, true)) {
            return false;
        }
        if (!is_file($root . '.htaccess')) {
            @file_put_contents($root . '.htaccess', "Require all denied\nDeny from all\n");
        }
        if (!is_file($root . 'index.html')) {
            @file_put_contents($root . 'index.html', '');
        }
        return is_dir($dir) || @mkdir($dir, 0755, true);
    }

    public static function decode(array $entry)
    {
        $entry['fields'] = json_decode((string)$entry['fields'], true) ?: array();
        return $entry;
    }

    public static function entries($limit, $offset = 0)
    {
        $rows = ipDb()->fetchAll(
            'SELECT * FROM ' . ipTable('formsubmissions_entry') . ' ORDER BY `id` DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset
        );
        return array_map(array(__CLASS__, 'decode'), $rows);
    }

    public static function count()
    {
        return (int)ipDb()->fetchValue('SELECT COUNT(*) FROM ' . ipTable('formsubmissions_entry'));
    }

    public static function unreadCount()
    {
        return (int)ipDb()->fetchValue('SELECT COUNT(*) FROM ' . ipTable('formsubmissions_entry') . ' WHERE `readAt` IS NULL');
    }

    public static function get($id)
    {
        $row = ipDb()->selectRow('formsubmissions_entry', '*', array('id' => (int)$id));
        return $row ? self::decode($row) : null;
    }

    public static function markRead($id)
    {
        ipDb()->execute('UPDATE ' . ipTable('formsubmissions_entry') . ' SET `readAt` = ? WHERE `id` = ? AND `readAt` IS NULL', array(time(), (int)$id));
    }

    public static function delete($id)
    {
        $entry = ipDb()->selectRow('formsubmissions_entry', array('id', 'fileDir'), array('id' => (int)$id));
        if (!$entry) {
            return;
        }
        if ($entry['fileDir']) {
            self::removeDir(self::fileDir($entry['fileDir']));
        }
        ipDb()->delete('formsubmissions_entry', array('id' => (int)$id));
    }

    public static function deleteAll()
    {
        self::deleteAllFiles();
        ipDb()->execute('DELETE FROM ' . ipTable('formsubmissions_entry'));
    }

    public static function deleteAllFiles()
    {
        $root = ipFile(self::FILE_ROOT);
        foreach (glob($root . '*', GLOB_ONLYDIR) ?: array() as $dir) {
            self::removeDir($dir . '/');
        }
    }

    protected static function removeDir($dir)
    {
        if (!is_dir($dir) || strpos(realpath($dir), realpath(ipFile(self::FILE_ROOT))) !== 0) {
            return;
        }
        foreach (glob($dir . '*') ?: array() as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }

    public static function keepDays()
    {
        return max(0, (int)ipGetOption('FormSubmissions.keepDays', 365));
    }

    public static function prune()
    {
        $days = self::keepDays();
        if (!$days) {
            return;
        }
        $old = ipDb()->fetchAll('SELECT `id` FROM ' . ipTable('formsubmissions_entry') . ' WHERE `createdAt` < ?', array(time() - $days * 86400));
        foreach ($old as $row) {
            self::delete($row['id']);
        }
    }

    /**
     * A short line for the list: the first few non-empty values.
     */
    public static function summary(array $entry)
    {
        $parts = array();
        foreach ($entry['fields'] as $field) {
            $value = trim(preg_replace('/\s+/', ' ', (string)$field['value']));
            if ($value !== '' && $field['type'] !== 'File') {
                $parts[] = $value;
            }
            if (count($parts) >= 3) {
                break;
            }
        }
        $text = implode(' · ', $parts);
        return mb_strlen($text) > 140 ? mb_substr($text, 0, 137) . '…' : $text;
    }
}
