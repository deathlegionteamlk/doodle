<?php /** @var array $data */
extract($data, EXTR_SKIP);
$l = $lesson;
?>
<div class="breadcrumb">
  <a href="<?= url('course/learn/' . $l['course_id']) ?>"><?= e($l['title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <span>AI Summary</span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><span class="material-icons" style="vertical-align:middle;color:var(--primary);">smart_toy</span> AI Summary</h1>
    <p><?= e($l['title']) ?></p>
  </div>
  <div class="actions">
    <a href="<?= url('course/learn/' . $l['course_id'] . '/' . $l['id']) ?>" class="btn btn-secondary"><span class="material-icons">arrow_back</span> Back to lesson</a>
  </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
  <div class="card card-pad">
    <h3 style="margin-bottom: 14px; display:flex;align-items:center;gap:8px;">
      <span class="material-icons" style="color:var(--primary);">summarize</span> Summary
    </h3>
    <div style="line-height:1.8;font-size:1rem;"><?= nl2br(e($summary)) ?></div>
  </div>

  <aside>
    <div class="card card-pad mb-3">
      <h3 style="margin-bottom: 12px;">Key terms</h3>
      <div class="flex" style="flex-wrap:wrap;gap:6px;">
        <?php foreach ($keywords as $kw): ?>
          <span class="badge badge-primary" style="font-size:.85rem;padding:5px 12px;"><?= e($kw) ?></span>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card card-pad" style="background:linear-gradient(135deg,var(--primary-light),#fff);border-color:var(--primary);">
      <h3 style="margin-bottom: 10px; display:flex;align-items:center;gap:6px;">
        <span class="material-icons" style="color:var(--primary);">auto_awesome</span> What's next?
      </h3>
      <div class="flex" style="flex-direction:column;gap:6px;">
        <a href="<?= url('ai/new?course_id=' . $l['course_id']) ?>" class="btn btn-primary btn-sm btn-block" style="justify-content:flex-start;">
          <span class="material-icons">chat</span> Ask AI about this lesson
        </a>
        <a href="<?= url('flashcards/generateFromCourse/' . $l['course_id']) ?>" class="btn btn-secondary btn-sm btn-block" style="justify-content:flex-start;">
          <span class="material-icons">style</span> Generate flashcards
        </a>
        <a href="<?= url('ai/generateQuiz/' . $l['course_id']) ?>" class="btn btn-secondary btn-sm btn-block" style="justify-content:flex-start;">
          <span class="material-icons">quiz</span> Practice quiz
        </a>
      </div>
    </div>
  </aside>
</div>
