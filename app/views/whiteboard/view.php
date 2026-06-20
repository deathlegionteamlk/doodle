<?php /** @var array $data */
extract($data, EXTR_SKIP);
$b = $board;
?>
<div class="breadcrumb">
  <a href="<?= url('course/view/' . $b['course_id']) ?>"><?= e($b['course_title']) ?></a>
  <span class="material-icons">chevron_right</span>
  <a href="<?= url('whiteboard/course/' . $b['course_id']) ?>">Whiteboards</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($b['name']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1><?= e($b['name']) ?></h1>
    <p><?= e($b['course_title']) ?> · shared live whiteboard</p>
  </div>
  <div class="actions">
    <button class="btn btn-secondary btn-sm" id="clearMyBtn"><span class="material-icons">undo</span> Undo mine</button>
    <?php if (Auth::isAdmin() || (Auth::isTeacher() && $b['course_id'] == Auth::id()) || $b['created_by'] == Auth::id()): ?>
      <form method="post" action="<?= url('whiteboard/view/' . $b['id']) ?>" style="display:inline;">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <input type="hidden" name="action" value="clear">
        <button class="btn btn-danger btn-sm" data-confirm="Clear all strokes?"><span class="material-icons">delete</span> Clear all</button>
      </form>
      <a href="<?= url('whiteboard/delete/' . $b['id']) ?>" class="btn btn-danger btn-sm" data-confirm="Delete this whiteboard?"><span class="material-icons">delete_forever</span></a>
    <?php endif; ?>
  </div>
</div>

<div class="whiteboard-wrap">
  <div class="wb-toolbar">
    <div class="wb-tools">
      <button class="wb-tool active" data-tool="pen" title="Pen"><span class="material-icons">edit</span></button>
      <button class="wb-tool" data-tool="eraser" title="Eraser"><span class="material-icons">brush</span></button>
      <button class="wb-tool" data-tool="rect" title="Rectangle"><span class="material-icons">crop_square</span></button>
      <button class="wb-tool" data-tool="circle" title="Circle"><span class="material-icons">panorama_fish_eye</span></button>
      <button class="wb-tool" data-tool="line" title="Line"><span class="material-icons">horizontal_rule</span></button>
    </div>
    <div class="wb-colors">
      <button class="wb-color active" style="background:#4F46E5" data-color="#4F46E5"></button>
      <button class="wb-color" style="background:#059669" data-color="#059669"></button>
      <button class="wb-color" style="background:#D97706" data-color="#D97706"></button>
      <button class="wb-color" style="background:#DC2626" data-color="#DC2626"></button>
      <button class="wb-color" style="background:#EC4899" data-color="#EC4899"></button>
      <button class="wb-color" style="background:#0F172A" data-color="#0F172A"></button>
    </div>
    <div class="wb-size">
      <label>Size: <input type="range" id="wbSize" min="1" max="30" value="3"></label>
    </div>
    <div class="wb-info">
      <span id="wbStatus" class="badge badge-success">Live</span>
      <span class="text-muted" style="font-size:.78rem;"><?= count($strokes) ?> strokes</span>
    </div>
  </div>

  <canvas id="whiteboard" width="1200" height="700"></canvas>
</div>

<style>
.whiteboard-wrap { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
.wb-toolbar { display: flex; align-items: center; gap: 14px; padding: 10px 14px; border-bottom: 1px solid var(--border); flex-wrap: wrap; background: var(--surface-2); }
.wb-tools, .wb-colors { display: flex; gap: 4px; }
.wb-tool { width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-muted); }
.wb-tool:hover { background: var(--primary-light); color: var(--primary); }
.wb-tool.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.wb-color { width: 28px; height: 28px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; padding: 0; }
.wb-color.active { border-color: var(--text); transform: scale(1.15); }
.wb-size label { display: flex; align-items: center; gap: 8px; font-size: .82rem; color: var(--text-muted); }
.wb-info { margin-left: auto; display: flex; align-items: center; gap: 10px; }
#whiteboard { display: block; width: 100%; height: 70vh; cursor: crosshair; background: #fff; touch-action: none; }
html[data-theme="dark"] #whiteboard { background: #FAFAFC; }
</style>

<script>
const boardId = <?= $b['id'] ?>;
const csrfToken = '<?= CSRF::token() ?>';
const initialStrokes = <?= json_encode($strokes) ?>;
let currentTool = 'pen';
let currentColor = '#4F46E5';
let currentSize = 3;
let drawing = false;
let myStrokes = [];
let lastX = 0, lastY = 0;
let strokeStart = null;

const canvas = document.getElementById('whiteboard');
const ctx = canvas.getContext('2d');

// Resize canvas to fit container
function resizeCanvas() {
  const rect = canvas.getBoundingClientRect();
  const ratio = window.devicePixelRatio || 1;
  canvas.width = rect.width * ratio;
  canvas.height = rect.height * ratio;
  ctx.scale(ratio, ratio);
  redraw();
}
window.addEventListener('resize', resizeCanvas);

function getPos(e) {
  const rect = canvas.getBoundingClientRect();
  const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
  const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
  return [x, y];
}

function drawStroke(s) {
  ctx.strokeStyle = s.color;
  ctx.fillStyle = s.color;
  ctx.lineWidth = s.size || 3;
  ctx.lineCap = 'round'; ctx.lineJoin = 'round';
  if (s.tool === 'pen' || s.tool === 'eraser') {
    if (s.tool === 'eraser') { ctx.globalCompositeOperation = 'destination-out'; } else { ctx.globalCompositeOperation = 'source-over'; }
    const pts = s.points || [];
    ctx.beginPath();
    if (pts.length > 0) {
      ctx.moveTo(pts[0][0], pts[0][1]);
      for (let i = 1; i < pts.length; i++) ctx.lineTo(pts[i][0], pts[i][1]);
      ctx.stroke();
    }
    ctx.globalCompositeOperation = 'source-over';
  } else if (s.tool === 'rect') {
    const [x1,y1,x2,y2] = s.coords;
    ctx.strokeRect(Math.min(x1,x2), Math.min(y1,y2), Math.abs(x2-x1), Math.abs(y2-y1));
  } else if (s.tool === 'circle') {
    const [x1,y1,x2,y2] = s.coords;
    const r = Math.hypot(x2-x1, y2-y1);
    ctx.beginPath(); ctx.arc(x1, y1, r, 0, Math.PI*2); ctx.stroke();
  } else if (s.tool === 'line') {
    const [x1,y1,x2,y2] = s.coords;
    ctx.beginPath(); ctx.moveTo(x1,y1); ctx.lineTo(x2,y2); ctx.stroke();
  }
}

function redraw() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  initialStrokes.forEach(s => {
    try { const d = JSON.parse(s.stroke_data); drawStroke({...d, color: s.color, tool: s.tool}); } catch(e){}
  });
  myStrokes.forEach(s => drawStroke(s));
}

// Tool selection
document.querySelectorAll('.wb-tool').forEach(b => {
  b.addEventListener('click', () => {
    document.querySelectorAll('.wb-tool').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    currentTool = b.dataset.tool;
  });
});
document.querySelectorAll('.wb-color').forEach(b => {
  b.addEventListener('click', () => {
    document.querySelectorAll('.wb-color').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    currentColor = b.dataset.color;
  });
});
document.getElementById('wbSize').addEventListener('input', e => currentSize = +e.target.value);

// Drawing handlers
let currentStroke = null;
function startDraw(e) {
  e.preventDefault();
  drawing = true;
  [lastX, lastY] = getPos(e);
  strokeStart = [lastX, lastY];
  if (currentTool === 'pen' || currentTool === 'eraser') {
    currentStroke = { tool: currentTool, color: currentColor, size: currentSize, points: [[lastX, lastY]] };
  }
}
function moveDraw(e) {
  if (!drawing) return;
  e.preventDefault();
  const [x, y] = getPos(e);
  if (currentTool === 'pen' || currentTool === 'eraser') {
    currentStroke.points.push([x, y]);
    // Render incrementally
    ctx.strokeStyle = currentColor;
    ctx.lineWidth = currentSize;
    ctx.lineCap = 'round';
    if (currentTool === 'eraser') ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(x, y); ctx.stroke();
    ctx.globalCompositeOperation = 'source-over';
  } else {
    // Preview shape
    redraw();
    drawStroke({ tool: currentTool, color: currentColor, size: currentSize, coords: [strokeStart[0], strokeStart[1], x, y] });
  }
  [lastX, lastY] = [x, y];
}
function endDraw(e) {
  if (!drawing) return;
  drawing = false;
  let stroke = currentStroke;
  if (currentTool !== 'pen' && currentTool !== 'eraser') {
    const [x, y] = getPos(e.changedTouches ? {clientX:e.changedTouches[0].clientX, clientY:e.changedTouches[0].clientY} : e);
    stroke = { tool: currentTool, color: currentColor, size: currentSize, coords: [strokeStart[0], strokeStart[1], x, y] };
  }
  if (stroke) {
    myStrokes.push(stroke);
    sendStroke(stroke);
    currentStroke = null;
  }
}

canvas.addEventListener('mousedown', startDraw);
canvas.addEventListener('mousemove', moveDraw);
canvas.addEventListener('mouseup', endDraw);
canvas.addEventListener('mouseleave', endDraw);
canvas.addEventListener('touchstart', startDraw, {passive:false});
canvas.addEventListener('touchmove', moveDraw, {passive:false});
canvas.addEventListener('touchend', endDraw);

function sendStroke(s) {
  fetch('<?= url('whiteboard/addStroke') ?>', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'whiteboard_id=' + boardId + '&stroke_data=' + encodeURIComponent(JSON.stringify(s)) +
          '&color=' + encodeURIComponent(s.color) + '&tool=' + encodeURIComponent(s.tool) +
          '&<?= CSRF_TOKEN_NAME ?>=' + csrfToken
  });
}

// Undo my strokes
document.getElementById('clearMyBtn').addEventListener('click', () => {
  myStrokes = [];
  redraw();
});

// Poll for new strokes every 2 seconds
let lastStrokeId = initialStrokes.length ? Math.max(...initialStrokes.map(s => s.id)) : 0;
setInterval(() => {
  fetch('<?= url("whiteboard/poll/") ?>' + boardId + '/' + lastStrokeId)
    .then(r => r.json())
    .then(d => {
      if (d.ok && d.strokes && d.strokes.length) {
        d.strokes.forEach(s => {
          try {
            const data = JSON.parse(s.stroke_data);
            initialStrokes.push({...s, ...data});
            drawStroke({...data, color: s.color, tool: s.tool});
            if (s.id > lastStrokeId) lastStrokeId = s.id;
          } catch(e){}
        });
        document.getElementById('wbStatus').textContent = 'Live';
      }
    });
}, 2000);

resizeCanvas();
redraw();
</script>
