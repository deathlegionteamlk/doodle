<?php /** @var array $data */
extract($data, EXTR_SKIP);
$s = $stats;
?>
<div class="page-head">
  <div class="title-block">
    <h1>Analytics</h1>
    <p><?= Auth::isAdmin() ? 'Platform-wide' : 'Your courses' ?> performance and engagement metrics.</p>
  </div>
</div>

<?php if (Auth::isAdmin()): ?>
  <div class="grid grid-4 mb-3">
    <div class="stat"><div><div class="stat-label">Total Users</div><div class="stat-value"><?= number_format($s['totalUsers']) ?></div></div><div class="stat-icon"><span class="material-icons">group</span></div></div>
    <div class="stat success"><div><div class="stat-label">Total Courses</div><div class="stat-value"><?= number_format($s['totalCourses']) ?></div></div><div class="stat-icon"><span class="material-icons">school</span></div></div>
    <div class="stat info"><div><div class="stat-label">Enrollments</div><div class="stat-value"><?= number_format($s['totalEnrollments']) ?></div></div><div class="stat-icon"><span class="material-icons">how_to_reg</span></div></div>
    <div class="stat accent"><div><div class="stat-label">Avg Progress</div><div class="stat-value"><?= round($s['avgProgress'], 1) ?>%</div></div><div class="stat-icon"><span class="material-icons">trending_up</span></div></div>
  </div>

  <div class="grid grid-2" style="align-items:flex-start;">
    <div class="card card-pad">
      <h3 style="margin-bottom: 14px;">Activity (last 30 days)</h3>
      <?php if (empty($s['activityByDay'])): ?>
        <p class="text-muted">No recent activity.</p>
      <?php else: ?>
        <div class="chart-bars">
          <?php
          $maxVal = 1;
          foreach ($s['activityByDay'] as $a) $maxVal = max($maxVal, $a['enrollments'] + $a['submissions'] + $a['lesson_completions']);
          foreach ($s['activityByDay'] as $date => $a):
            $total = $a['enrollments'] + $a['submissions'] + $a['lesson_completions'];
            $h = ($total / $maxVal) * 100;
          ?>
            <div class="chart-bar" title="<?= e($date) ?>: <?= $total ?> events">
              <div style="height:<?= $h ?>%;background:linear-gradient(180deg,var(--primary),var(--accent));border-radius:3px 3px 0 0;"></div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="flex gap-lg mt-2" style="font-size:.78rem;color:var(--text-muted);">
          <span><span style="display:inline-block;width:10px;height:10px;background:var(--primary);border-radius:2px;vertical-align:middle;margin-right:4px;"></span> Activity events</span>
        </div>
      <?php endif; ?>
    </div>

    <div class="card card-pad">
      <h3 style="margin-bottom: 14px;">Completion rates (top courses)</h3>
      <?php if (empty($s['completionRates'])): ?>
        <p class="text-muted">No data yet.</p>
      <?php else: foreach ($s['completionRates'] as $c): ?>
        <div style="margin-bottom:14px;">
          <div class="flex-between" style="font-size:.85rem;margin-bottom:4px;">
            <strong><?= e(truncate($c['title'], 30)) ?></strong>
            <span class="text-muted"><?= (int) $c['completed'] ?>/<?= (int) $c['enrolled'] ?></span>
          </div>
          <?php $pct = $c['enrolled'] > 0 ? round(($c['completed'] / $c['enrolled']) * 100, 1) : 0; ?>
          <div class="progress" style="height:8px;"><div class="progress-bar" style="width:<?= $pct ?>%;"></div></div>
          <small class="text-muted"><?= $pct ?>% completion</small>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-head"><h3>Top courses by enrollment</h3></div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Course</th><th>Enrolled</th><th>Total (all-time)</th><th>Completion %</th></tr></thead>
        <tbody>
          <?php foreach ($s['topCourses'] as $i => $c): ?>
            <?php
              $completed = $s['completionRates'][$i]['completed'] ?? 0;
              $enrolled = $s['completionRates'][$i]['enrolled'] ?? 0;
              $pct = $enrolled > 0 ? round(($completed / $enrolled) * 100, 1) : 0;
            ?>
            <tr>
              <td><strong><?= e($c['title']) ?></strong></td>
              <td><?= (int) $c['enroll_count'] ?></td>
              <td class="text-muted"><?= (int) $c['total'] ?></td>
              <td>
                <span class="badge badge-<?= $pct >= 60 ? 'success' : ($pct >= 30 ? 'warning' : 'danger') ?>"><?= $pct ?>%</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: /* teacher */ ?>
  <div class="grid grid-2" style="align-items:flex-start;">
    <div class="card card-pad">
      <h3 style="margin-bottom: 14px;">Student activity (last 30 days)</h3>
      <?php if (empty($s['activityByDay'])): ?>
        <p class="text-muted">No recent activity.</p>
      <?php else: ?>
        <div class="chart-bars">
          <?php
          $maxVal = max(1, max($s['activityByDay']));
          foreach ($s['activityByDay'] as $date => $count):
            $h = ($count / $maxVal) * 100;
          ?>
            <div class="chart-bar" title="<?= e($date) ?>: <?= $count ?> completions">
              <div style="height:<?= $h ?>%;background:linear-gradient(180deg,var(--primary),var(--accent));border-radius:3px 3px 0 0;"></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card card-pad">
      <h3 style="margin-bottom: 14px;">Submission grading</h3>
      <?php $stat = $s['submissionStats']; ?>
      <div style="font-size:.92rem;line-height:1.8;">
        <div class="flex-between"><span class="text-muted">Total submissions</span><strong><?= (int) $stat['total'] ?></strong></div>
        <div class="flex-between"><span class="text-muted">Pending grading</span><strong style="color:var(--warning);"><?= (int) $stat['pending'] ?></strong></div>
        <div class="flex-between"><span class="text-muted">Graded</span><strong style="color:var(--success);"><?= (int) $stat['graded'] ?></strong></div>
        <div class="flex-between"><span class="text-muted">Average score</span><strong style="color:var(--primary);"><?= round((float) $stat['avg_score'], 1) ?></strong></div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-head"><h3>Course performance</h3></div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Course</th><th>Enrolled</th><th>Avg progress</th><th>Completed</th></tr></thead>
        <tbody>
          <?php foreach ($s['courses'] as $c): ?>
            <tr>
              <td><strong><?= e($c['title']) ?></strong></td>
              <td><?= (int) $c['enrolled'] ?></td>
              <td>
                <div class="progress" style="height:8px;"><div class="progress-bar" style="width:<?= round((float) $c['avg_progress']) ?>%;"></div></div>
                <small class="text-muted"><?= round((float) $c['avg_progress'], 1) ?>%</small>
              </td>
              <td><?= (int) $c['completed'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<style>
.chart-bars { display: flex; gap: 2px; align-items: flex-end; height: 180px; padding: 10px 0; border-bottom: 1px solid var(--border); overflow-x: auto; }
.chart-bar { flex: 1; min-width: 8px; height: 100%; display: flex; align-items: flex-end; }
.chart-bar > div { width: 100%; min-height: 2px; }
</style>
