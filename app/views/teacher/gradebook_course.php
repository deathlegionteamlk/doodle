<?php /** @var array $data */
extract($data, EXTR_SKIP);
$c = $course;
?>
<div class="breadcrumb">
  <a href="<?= url('teacher/gradebook') ?>">Gradebook</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($c['title']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>Gradebook · <?= e($c['title']) ?></h1>
    <p><?= count($students) ?> students · <?= count($assignments) ?> graded items</p>
  </div>
  <div class="actions">
    <a href="<?= url('teacher/courses/edit/' . $c['id']) ?>" class="btn btn-secondary"><span class="material-icons">edit</span> Manage course</a>
  </div>
</div>

<?php if (empty($students) || empty($assignments)): ?>
  <div class="card card-pad empty">
    <span class="material-icons">grading</span>
    <h3>Nothing to display</h3>
    <p>You need at least one enrolled student and one assignment to use the gradebook.</p>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>Student</th>
            <?php foreach ($assignments as $a): ?>
              <th style="text-align:center;min-width:80px;">
                <?= e(truncate($a['title'], 14)) ?>
                <div style="font-weight:400;font-size:.72rem;color:var(--text-muted);">/ <?= $a['max_score'] ?></div>
              </th>
            <?php endforeach; ?>
            <th style="text-align:center;">Total</th>
            <th style="text-align:center;">%</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $maxTotal = 0; foreach ($assignments as $a) $maxTotal += (float)$a['max_score'];
          foreach ($students as $st):
            $total = 0; $hasGrades = false;
          ?>
            <tr>
              <td>
                <div class="flex gap-sm">
                  <span class="avatar sm" style="background:<?= avatarColor($st['full_name']) ?>"><?= initials($st['full_name']) ?></span>
                  <div>
                    <strong><?= e($st['full_name']) ?></strong>
                    <div class="text-muted" style="font-size:.78rem;"><?= e($st['email']) ?></div>
                  </div>
                </div>
              </td>
              <?php foreach ($assignments as $a):
                $g = $grades[$st['id']][$a['id']] ?? null;
                if ($g && $g['score'] !== null) { $total += (float)$g['score']; $hasGrades = true; }
              ?>
                <td style="text-align:center;">
                  <?php if ($g && $g['score'] !== null): ?>
                    <strong><?= $g['score'] ?></strong>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
              <td style="text-align:center;">
                <strong><?= $hasGrades ? $total : '—' ?></strong>
                <span class="text-muted" style="font-size:.78rem;">/ <?= $maxTotal ?></span>
              </td>
              <td style="text-align:center;">
                <?php if ($hasGrades && $maxTotal > 0):
                  $pct = round(($total / $maxTotal) * 100, 1);
                  $color = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
                ?>
                  <span class="badge badge-<?= $color ?>" style="font-size:.85rem;"><?= $pct ?>%</span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
