<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block"><h1>My Bookmarks</h1><p>Lessons you've saved for later.</p></div>
</div>
<?php if (empty($bookmarks)): ?>
  <div class="card card-pad empty"><span class="material-icons">bookmark</span><h3>No bookmarks yet</h3><p>Click the bookmark icon on any lesson to save it here.</p></div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Lesson</th><th>Course</th><th>Bookmarked</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($bookmarks as $b): ?>
          <tr>
            <td><strong><?= e($b['lesson_title']) ?></strong></td>
            <td class="text-muted" style="font-size:.85rem;"><?= e($b['course_title']) ?></td>
            <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($b['created_at']) ?></td>
            <td class="text-right"><a href="<?= url('course/learn/' . $b['course_id'] . '/' . $b['lesson_id']) ?>" class="btn btn-primary btn-sm">Open</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
<?php endif; ?>
