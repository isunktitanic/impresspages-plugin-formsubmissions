<?php

namespace Plugin\FormSubmissions;

class AdminController
{
    const PER_PAGE = 50;

    protected static $notices = array(
        'deleted' => 'Submission deleted.',
        'deletedAll' => 'All submissions deleted.',
        'unread' => 'Marked as unread.',
    );

    public function index()
    {
        Model::prune();
        ipAddJs('Plugin/FormSubmissions/assets/admin.js');

        $page = max(1, (int)ipRequest()->getQuery('p', 1));
        $total = Model::count();

        return ipView('view/list.php', array(
            'entries' => Model::entries(self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'total' => $total,
            'unread' => Model::unreadCount(),
            'page' => $page,
            'pages' => max(1, (int)ceil($total / self::PER_PAGE)),
            'notice' => self::$notices[(string)ipRequest()->getQuery('done')] ?? null,
            'keepDays' => Model::keepDays(),
        ));
    }

    public function view()
    {
        $entry = Model::get(ipRequest()->getQuery('id'));
        if (!$entry) {
            return $this->back('');
        }
        Model::markRead($entry['id']);
        ipAddJs('Plugin/FormSubmissions/assets/admin.js');

        $page = $entry['pageId'] ? ipContent()->getPage($entry['pageId']) : null;
        return ipView('view/entry.php', array(
            'entry' => $entry,
            'pageUrl' => $page ? $page->getLink() : null,
        ));
    }

    public function markUnread()
    {
        ipRequest()->mustBePost();
        ipDb()->update('formsubmissions_entry', array('readAt' => null), array('id' => (int)ipRequest()->getPost('id')));
        return $this->back('unread');
    }

    public function delete()
    {
        ipRequest()->mustBePost();
        $id = ipRequest()->getPost('id');
        if ($id === 'all') {
            Model::deleteAll();
            ipLog()->info('FormSubmissions.deletedAll', array('adminId' => ipAdminId()));
            return $this->back('deletedAll');
        }
        Model::delete($id);
        return $this->back('deleted');
    }

    /**
     * An attached file, only for logged-in administrators with access to this plugin.
     */
    public function download()
    {
        $entry = Model::get(ipRequest()->getQuery('id'));
        $stored = (string)ipRequest()->getQuery('file');
        if (!$entry || !$entry['fileDir']) {
            return new \Ip\Response\PageNotFound();
        }
        foreach ($entry['fields'] as $field) {
            foreach ($field['files'] ?? array() as $file) {
                if ($file['stored'] === $stored) {
                    $path = Model::fileDir($entry['fileDir']) . $file['stored'];
                    if (!is_file($path)) {
                        break 2;
                    }
                    $response = new \Ip\Response(file_get_contents($path));
                    $response->addHeader('Content-Type: application/octet-stream');
                    $response->addHeader('Content-Disposition: attachment; filename="' . Model::safeName($file['name']) . '"');
                    $response->addHeader('X-Content-Type-Options: nosniff');
                    return $response;
                }
            }
        }
        return new \Ip\Response\PageNotFound();
    }

    public function export()
    {
        $entries = Model::entries(100000);
        $labels = array();
        foreach ($entries as $entry) {
            foreach ($entry['fields'] as $field) {
                if (!in_array($field['label'], $labels, true)) {
                    $labels[] = $field['label'];
                }
            }
        }

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // so spreadsheet apps read the file as UTF-8
        fputcsv($out, array_merge(array('Received', 'Page', 'Read'), $labels), ',', '"', '');
        foreach (array_reverse($entries) as $entry) {
            $values = array_fill_keys($labels, '');
            foreach ($entry['fields'] as $field) {
                $value = (string)$field['value'];
                if (!empty($field['files'])) {
                    $value = implode(', ', array_column($field['files'], 'name'));
                }
                $values[$field['label']] = self::csvSafe($value);
            }
            fputcsv($out, array_merge(array(
                date('Y-m-d H:i', (int)$entry['createdAt']),
                self::csvSafe((string)$entry['pageTitle']),
                $entry['readAt'] ? 'yes' : 'no',
            ), array_values($values)), ',', '"', '');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new \Ip\Response($csv);
        $response->addHeader('Content-Type: text/csv; charset=utf-8');
        $response->addHeader('Content-Disposition: attachment; filename="form-submissions-' . date('Y-m-d') . '.csv"');
        return $response;
    }

    /**
     * Values a visitor typed must not run as formulas when the file is opened in a spreadsheet.
     */
    protected static function csvSafe($value)
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    protected function back($done)
    {
        $response = new \Ip\Response('', null, 303);
        $response->addHeader('HTTP/1.1 303 See Other');
        $response->addHeader('Location: ' . ipActionUrl(array('aa' => 'FormSubmissions.index', 'done' => $done)));
        return $response;
    }
}
