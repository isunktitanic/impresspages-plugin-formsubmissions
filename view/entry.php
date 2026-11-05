<?php
/**
 * @var array $entry
 * @var string|null $pageUrl
 */
$token = escAttr(ipSecurityToken());
$size = function ($bytes) {
    return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, round($bytes / 1024)) . ' KB';
};
?>
<div class="ipModuleFormSubmissions" style="padding: 0 20px 40px; max-width: 900px;">
    <p><a href="<?php echo escAttr(ipActionUrl(array('aa' => 'FormSubmissions.index'))); ?>"><i class="fa fa-arrow-left" aria-hidden="true"></i> All submissions</a></p>

    <div class="page-header">
        <h1>Submission from <?php echo esc(date('Y-m-d H:i', (int)$entry['createdAt'])); ?></h1>
    </div>

    <p class="text-muted">
        Sent from
        <?php if ($pageUrl) { ?>
            <a href="<?php echo escAttr($pageUrl); ?>" target="_blank" rel="noopener"><?php echo esc($entry['pageTitle']); ?></a>
        <?php } else { ?>
            <?php echo esc($entry['pageTitle'] ?: 'a page that no longer exists'); ?>
        <?php } ?>
        <?php if ($entry['ip']) { ?> · IP address <?php echo esc($entry['ip']); ?><?php } ?>
    </p>

    <dl class="ipsSubmission">
        <?php foreach ($entry['fields'] as $field) { ?>
            <dt style="margin-top: 12px;"><?php echo esc($field['label'] !== '' ? $field['label'] : '(no label)'); ?></dt>
            <dd style="white-space: pre-wrap; overflow-wrap: anywhere;"><?php
                if (!empty($field['files'])) {
                    foreach ($field['files'] as $file) {
                        $link = ipActionUrl(array('aa' => 'FormSubmissions.download', 'id' => $entry['id'], 'file' => $file['stored']));
                        echo '<a href="' . escAttr($link) . '"><i class="fa fa-paperclip" aria-hidden="true"></i> ' . esc($file['name']) . '</a> <span class="text-muted">(' . esc($size((int)$file['size'])) . ')</span><br>';
                    }
                } elseif ($field['type'] === 'Email' && $field['value'] !== '') {
                    echo '<a href="mailto:' . escAttr($field['value']) . '">' . esc($field['value']) . '</a>';
                } else {
                    echo $field['value'] !== '' ? esc($field['value']) : '<span class="text-muted">—</span>';
                }
            ?></dd>
        <?php } ?>
    </dl>

    <div style="margin-top: 24px; display: flex; gap: 8px; flex-wrap: wrap;">
        <?php if ($entry['senderEmail']) { ?>
            <a class="btn btn-primary" href="mailto:<?php echo escAttr($entry['senderEmail']); ?>?subject=<?php echo rawurlencode('Re: ' . ($entry['pageTitle'] ?: 'your message')); ?>"><i class="fa fa-reply" aria-hidden="true"></i> Reply by email</a>
        <?php } ?>
        <form method="post" action="<?php echo escAttr(ipActionUrl(array('aa' => 'FormSubmissions.markUnread'))); ?>" style="margin: 0;">
            <input type="hidden" name="securityToken" value="<?php echo $token; ?>">
            <input type="hidden" name="id" value="<?php echo (int)$entry['id']; ?>">
            <button type="submit" class="btn btn-default">Mark as unread</button>
        </form>
        <form method="post" action="<?php echo escAttr(ipActionUrl(array('aa' => 'FormSubmissions.delete'))); ?>" style="margin: 0;"
              data-confirm="Delete this submission<?php echo $entry['fileDir'] ? ' and its attachments' : ''; ?>?" data-confirm-button="Delete">
            <input type="hidden" name="securityToken" value="<?php echo $token; ?>">
            <input type="hidden" name="id" value="<?php echo (int)$entry['id']; ?>">
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>
