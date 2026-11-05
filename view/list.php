<?php
/**
 * @var array $entries
 * @var int $total
 * @var int $unread
 * @var int $page
 * @var int $pages
 * @var string|null $notice
 * @var int $keepDays
 */
$token = escAttr(ipSecurityToken());
?>
<div class="ipModuleFormSubmissions" style="padding: 0 20px 40px; max-width: 1100px;">
    <div class="page-header">
        <h1>Form submissions</h1>
    </div>

    <p>
        Every message sent through a Form widget on your pages, newest first. The email is still sent as before; this is your copy.
        <?php if ($keepDays) { ?>
            Submissions are deleted after <?php echo (int)$keepDays; ?> days.
        <?php } ?>
    </p>

    <?php if ($notice) { ?>
        <p class="alert alert-success" role="status"><?php echo esc($notice); ?></p>
    <?php } ?>

    <?php if (!$total) { ?>
        <p class="text-muted">No submissions yet. They appear here as soon as a visitor sends a form.</p>
    <?php } else { ?>
        <p>
            <strong><?php echo (int)$total; ?></strong> submission<?php echo $total == 1 ? '' : 's'; ?><?php if ($unread) { ?>, <strong><?php echo (int)$unread; ?> unread</strong><?php } ?>.
            <a class="btn btn-default btn-sm" href="<?php echo escAttr(ipActionUrl(array('aa' => 'FormSubmissions.export'))); ?>"><i class="fa fa-download" aria-hidden="true"></i> Download all as CSV</a>
        </p>

        <div class="table-responsive" style="position: relative;">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th scope="col">Received</th>
                    <th scope="col">Page</th>
                    <th scope="col">Message</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry) {
                    $isUnread = !$entry['readAt'];
                    $hasFiles = (bool)$entry['fileDir'];
                    $url = ipActionUrl(array('aa' => 'FormSubmissions.view', 'id' => $entry['id']));
                    ?>
                    <tr<?php echo $isUnread ? ' style="font-weight: 600;"' : ''; ?>>
                        <td style="white-space: nowrap;">
                            <?php if ($isUnread) { ?><span class="label label-primary" style="margin-right: 4px;">New</span><?php } ?>
                            <?php echo esc(date('Y-m-d H:i', (int)$entry['createdAt'])); ?>
                        </td>
                        <td><?php echo esc($entry['pageTitle'] ?: '—'); ?></td>
                        <td style="overflow-wrap: anywhere;">
                            <a href="<?php echo escAttr($url); ?>"><?php echo esc(\Plugin\FormSubmissions\Model::summary($entry) ?: '(empty)'); ?></a>
                            <?php if ($hasFiles) { ?><i class="fa fa-paperclip" aria-label="Has attachments" title="Has attachments"></i><?php } ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <a class="btn btn-default btn-xs" href="<?php echo escAttr($url); ?>">Open</a>
                            <form method="post" action="<?php echo escAttr(ipActionUrl(array('aa' => 'FormSubmissions.delete'))); ?>" style="display: inline; margin: 0;"
                                  data-confirm="Delete this submission<?php echo $hasFiles ? ' and its attachments' : ''; ?>?" data-confirm-button="Delete">
                                <input type="hidden" name="securityToken" value="<?php echo $token; ?>">
                                <input type="hidden" name="id" value="<?php echo (int)$entry['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1) { ?>
            <nav aria-label="Pages of submissions">
                <ul class="pager">
                    <?php if ($page > 1) { ?>
                        <li class="previous"><a href="<?php echo escAttr(ipActionUrl(array('aa' => 'FormSubmissions.index', 'p' => $page - 1))); ?>">Newer</a></li>
                    <?php } ?>
                    <li>Page <?php echo (int)$page; ?> of <?php echo (int)$pages; ?></li>
                    <?php if ($page < $pages) { ?>
                        <li class="next"><a href="<?php echo escAttr(ipActionUrl(array('aa' => 'FormSubmissions.index', 'p' => $page + 1))); ?>">Older</a></li>
                    <?php } ?>
                </ul>
            </nav>
        <?php } ?>

        <form method="post" action="<?php echo escAttr(ipActionUrl(array('aa' => 'FormSubmissions.delete'))); ?>"
              data-confirm="Delete all <?php echo (int)$total; ?> submissions and their attachments? This can't be undone." data-confirm-button="Delete all">
            <input type="hidden" name="securityToken" value="<?php echo $token; ?>">
            <input type="hidden" name="id" value="all">
            <button type="submit" class="btn btn-danger btn-sm">Delete all</button>
        </form>
    <?php } ?>
</div>
