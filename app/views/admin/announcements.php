<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Announcements</h1>
    <p>Broadcast messages to all users or to specific course cohorts.</p>
  </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 360px; gap: 24px; align-items: flex-start;">
  <div class="card">
    <div class="card-head"><h3>Recent announcements</h3></div>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Title</th><th>Author</th><th>Audience</th><th>Course</th><th>Sent</th><th></th></tr>
        </thead>
        <tbody>
          <?php if (empty($announcements)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:30px;">No announcements sent yet.</td></tr>
          <?php else: foreach ($announcements as $a): ?>
            <tr>
              <td>
                <strong><?= e($a['title']) ?></strong>
                <div class="text-muted" style="font-size:.78rem;"><?= e(truncate($a['body'], 100)) ?></div>
              </td>
              <td><?= e($a['author_name']) ?></td>
              <td><span class="badge"><?= e($a['audience']) ?></span></td>
              <td class="text-muted" style="font-size:.82rem;"><?= e($a['course_title'] ?? 'All') ?></td>
              <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($a['created_at']) ?></td>
              <td class="text-right">
                <a href="<?= url('admin/deleteAnnouncement/' . $a['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete this announcement?">
                  <span class="material-icons">delete</span>
                </a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <aside class="card card-pad">
    <h3 style="margin-bottom: 14px;">New announcement</h3>
    <form method="post" action="<?= url('admin/announcements') ?>">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
      <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" required>
      </div>
      <div class="form-group">
        <label>Message</label>
        <textarea name="body" rows="5" required></textarea>
      </div>
      <div class="form-group">
        <label>Target audience</label>
        <select name="audience">
          <option value="all">All users</option>
          <option value="students">Students only</option>
          <option value="teachers">Teachers only</option>
        </select>
      </div>
      <div class="form-group">
        <label>Specific course (optional)</label>
        <select name="course_id">
          <option value="0">— All courses —</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><span class="material-icons">send</span> Broadcast</button>
    </form>
  </aside>
</div>
