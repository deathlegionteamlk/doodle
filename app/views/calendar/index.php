<?php /** @var array $data */
extract($data, EXTR_SKIP);
?>
<div class="page-head">
  <div class="title-block">
    <h1>Calendar</h1>
    <p>All your assignment deadlines and events in one place.</p>
  </div>
  <div class="actions">
    <a href="<?= url('calendar/ical') ?>" class="btn btn-secondary" download><span class="material-icons">download</span> Export iCal</a>
    <button class="btn btn-primary" onclick="document.getElementById('newEventModal').style.display='flex'"><span class="material-icons">add</span> New event</button>
  </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
  <div class="card card-pad">
    <div class="flex-between mb-3">
      <a href="<?= url($prevLink) ?>" class="btn btn-ghost btn-sm"><span class="material-icons">chevron_left</span></a>
      <h2 style="margin:0;"><?= e($monthName) ?></h2>
      <a href="<?= url($nextLink) ?>" class="btn btn-ghost btn-sm"><span class="material-icons">chevron_right</span></a>
    </div>
    <div class="calendar-grid">
      <div class="calendar-weekday">Sun</div>
      <div class="calendar-weekday">Mon</div>
      <div class="calendar-weekday">Tue</div>
      <div class="calendar-weekday">Wed</div>
      <div class="calendar-weekday">Thu</div>
      <div class="calendar-weekday">Fri</div>
      <div class="calendar-weekday">Sat</div>
      <?php foreach ($grid as $week): foreach ($week as $cell): ?>
        <div class="calendar-day <?= !$cell['in_month'] ? 'out-of-month' : '' ?> <?= !empty($cell['today']) ? 'today' : '' ?>">
          <div class="day-num"><?= $cell['day'] ?></div>
          <?php foreach ($cell['events'] as $e): ?>
            <a href="<?= url($e['link']) ?>" class="calendar-event" style="background:<?= $e['color'] ?>22;color:<?= $e['color'] ?>;border-left:3px solid <?= $e['color'] ?>;">
              <span class="material-icons" style="font-size:11px;vertical-align:middle;"><?= $e['icon'] ?></span>
              <?= e(truncate($e['title'], 20)) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; endforeach; ?>
    </div>
  </div>

  <aside>
    <div class="card card-pad">
      <h3 style="margin-bottom: 12px;">Upcoming (7 days)</h3>
      <?php if (empty($upcoming)): ?>
        <p class="text-muted" style="font-size:.88rem;padding:14px 0;">Nothing due this week. 🎉</p>
      <?php else: ?>
        <?php foreach ($upcoming as $e): ?>
          <a href="<?= url($e['link']) ?>" class="upcoming-event">
            <span class="material-icons" style="color:<?= $e['color'] ?>;font-size:18px;"><?= $e['icon'] ?></span>
            <div>
              <strong style="font-size:.85rem;"><?= e($e['title']) ?></strong>
              <div class="text-muted" style="font-size:.72rem;"><?= e($e['course']) ?> · <?= formatDate($e['date'], 'M j') ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="card card-pad">
      <h3 style="margin-bottom: 10px;">Legend</h3>
      <div style="font-size:.85rem;line-height:1.8;">
        <div><span style="display:inline-block;width:12px;height:12px;background:var(--warning);border-radius:2px;vertical-align:middle;margin-right:6px;"></span> Assignment due</div>
        <div><span style="display:inline-block;width:12px;height:12px;background:var(--primary);border-radius:2px;vertical-align:middle;margin-right:6px;"></span> Custom event</div>
      </div>
    </div>
  </aside>
</div>

<div id="newEventModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
  <div class="card" style="max-width:500px;width:100%;">
    <div class="card-head">
      <h3>New calendar event</h3>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('newEventModal').style.display='none'"><span class="material-icons">close</span></button>
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('calendar/create') ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Title</label>
          <input type="text" name="title" required>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Starts at</label>
            <input type="datetime-local" name="start_at" required>
          </div>
          <div class="form-group">
            <label>Ends at (optional)</label>
            <input type="datetime-local" name="end_at">
          </div>
        </div>
        <div class="form-group">
          <label>Type</label>
          <select name="event_type">
            <option value="reminder">Reminder</option>
            <option value="deadline">Deadline</option>
            <option value="live">Live session</option>
            <option value="exam">Exam</option>
            <option value="meeting">Meeting</option>
          </select>
        </div>
        <div class="form-group">
          <label>Location (optional)</label>
          <input type="text" name="location" placeholder="Room, Zoom link, etc.">
        </div>
        <div class="form-group">
          <label>Description (optional)</label>
          <textarea name="description" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Add event</button>
      </form>
    </div>
  </div>
</div>

<style>
.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.calendar-weekday { text-align: center; font-size: .78rem; font-weight: 700; color: var(--text-muted); padding: 8px 0; text-transform: uppercase; letter-spacing: .05em; }
.calendar-day { min-height: 100px; padding: 4px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--surface); overflow: hidden; }
.calendar-day.out-of-month { background: var(--surface-2); color: var(--text-light); }
.calendar-day.today { background: var(--primary-light); border-color: var(--primary); }
.day-num { font-size: .82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; }
.calendar-day.today .day-num { color: var(--primary); font-weight: 800; }
.calendar-event { display: block; padding: 3px 6px; border-radius: 4px; font-size: .72rem; margin-bottom: 2px; text-decoration: none; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.upcoming-event { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border); text-decoration: none; color: inherit; }
.upcoming-event:last-child { border-bottom: 0; }
@media (max-width: 720px) {
  .calendar-day { min-height: 70px; }
  .calendar-event { font-size: .65rem; }
}
</style>
