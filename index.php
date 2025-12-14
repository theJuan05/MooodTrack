<?php 
session_start();
include 'config.php';
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}


$user_id = $_SESSION['user_id'];

// Get filters
$filter_category = $_GET['category'] ?? '';
$filter_sentiment = $_GET['sentiment'] ?? '';
$success = isset($_GET['success']) ? true : false;

// Build query dynamically for mood history
$query = "SELECT * FROM moods WHERE user_id = ?";
$params = [$user_id];

if ($filter_category) {
    $query .= " AND category = ?";
    $params[] = $filter_category;
}

if ($filter_sentiment) {
    $query .= " AND sentiment = ?";
    $params[] = $filter_sentiment;
}

$query .= " ORDER BY created_at DESC LIMIT 10";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$moods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest mood submission
$latestStmt = $pdo->prepare("SELECT * FROM moods WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$latestStmt->execute([$user_id]);
$latestMood = $latestStmt->fetch(PDO::FETCH_ASSOC);

// Fetch last saved mood (most recently updated, could be edited)
$lastStmt = $pdo->prepare("SELECT * FROM moods WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
$lastStmt->execute([$user_id]);
$lastMood = $lastStmt->fetch(PDO::FETCH_ASSOC);

// Optional: If latestMood and lastMood are same, skip duplicate
if($latestMood && $lastMood && $latestMood['id'] == $lastMood['id']){
    $lastMood = null; // avoid showing same mood twice
}

// Calculate statistics
$total = count($moods);
$positive = $neutral = $negative = $anxious = $excited = $confused = 0;
foreach ($moods as $m) {
    switch ($m['sentiment']) {
        case 'Positive': $positive++; break;
        case 'Neutral':  $neutral++; break;
        case 'Negative': $negative++; break;
        case 'Anxious': $anxious++; break;
        case 'Excited': $excited++; break;
        case 'Confused': $confused++; break;
    }
}
$percent = fn($count) => $total ? round(($count/$total)*100,2) : 0;

// Sentiment color classes
$sentimentColors = [
    'Positive' => 'Positive',
    'Neutral'  => 'Neutral',
    'Negative' => 'Negative',
    'Anxious'  => 'Anxious',
    'Excited'  => 'Excited',
    'Confused' => 'Confused'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mood Tracker Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { 
    background: linear-gradient(180deg, #0a0130, #1c0068); 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    color: #fff;
}

.card {
    background: rgba(255,255,255,0.06) !important;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.09);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    color: #fff;
}

.form-control, .form-select, textarea {
    background: rgba(0,0,0,0.35) !important;
    border: 1px solid rgba(255,255,255,0.25) !important;
    color: #fff !important;
    border-radius: 10px !important;
}

.form-control::placeholder { color: #d0cfff !important; }
label { color: #fff !important; }
small.text-muted { color: #ccc !important; }

.btn-primary {
    background: #4b2cff !important;
    border: none;
    border-radius: 10px;
    padding: 12px;
}

.btn-primary:hover {
    background: #6c47ff !important;
}

.Positive { background-color: #d4edda !important; color:#000; }
.Neutral  { background-color: #fff3cd !important; color:#000; }
.Negative { background-color: #f8d7da !important; color:#000; }
.Anxious  { background-color: #ffeeba !important; color:#000; }
.Excited  { background-color: #d1ecf1 !important; color:#000; }
.Confused { background-color: #e2d6f7 !important; color:#000; }

.table {
    background: rgba(0,0,0,0.7) !important;
    color: #fff !important;
    border-radius: 12px;
    overflow: hidden;
}

.table thead {
    background: #000 !important;
    color: #fff !important;
}

.table tbody tr {
    backdrop-filter: blur(4px);
}

.table tbody tr:hover {
    background: rgba(255,255,255,0.08) !important;
    transition: 0.2s;
}

.form-floating label { color: #ccc !important; }
.form-floating textarea { padding-top: 2rem !important; }
.form-floating .form-select { padding-top: 1.6rem !important; padding-bottom: .6rem !important; }

.badge { padding: 6px 12px; border-radius: 8px; }
</style>
</head>

<body>
<div class="container py-5">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Mood Tracker Dashboard</h2>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>

    <!-- MOOD ENTRY CARD -->
    <div class="card mb-5">
        <div class="card-body">
            <h4 class="card-title mb-3">Enter Your Daily Mood</h4>

            <?php if ($success && $latestMood): 
                $colorClass = $sentimentColors[$latestMood['sentiment']] ?? 'Neutral';
            ?>
            <div class="alert <?= $colorClass ?> mt-2">
                <strong>Latest Submitted Mood:</strong> <?= htmlspecialchars($latestMood['sentiment']) ?><br>
                <strong>Explanation:</strong> <?= htmlspecialchars($latestMood['explanation']) ?>
            </div>
            <?php endif; ?>

            <!-- Last Saved Mood -->
            <?php if (!empty($lastMood)): 
                $lastColorClass = $sentimentColors[$lastMood['sentiment']] ?? 'Neutral';
            ?>
            <div class="alert <?= $lastColorClass ?> mt-3">
                <strong>Last Saved Mood:</strong> <?= htmlspecialchars($lastMood['sentiment']) ?><br>
                <strong>Explanation:</strong> <?= htmlspecialchars($lastMood['explanation']) ?>
            </div>
            <?php endif; ?>

            <form action="process.php" method="POST">
                <div class="form-floating mb-3">
                    <textarea name="entry" class="form-control" id="entryInput" placeholder="Write your mood..." style="height: 90px;" required></textarea>
                    <label for="entryInput">Mood Entry</label>
                </div>

                <div class="form-floating mb-3">
                    <textarea name="reason" class="form-control" id="reasonInput" placeholder="Reason" style="height: 70px;"></textarea>
                    <label for="reasonInput">Reason / Context (Optional)</label>
                </div>

                <div class="form-floating mb-3">
                    <select name="category" class="form-select" id="categoryInput">
                        <option value=""></option>
                        <option value="Work">Work</option>
                        <option value="Personal">Personal</option>
                        <option value="Health">Health</option>
                        <option value="Social">Social</option>
                    </select>
                    <label for="categoryInput">Category</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">Submit Mood</button>
            </form>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <select onchange="applyFilter()" id="filter_sentiment" class="form-control">
                <option value="">All Sentiments</option>
                <option value="Positive" <?= $filter_sentiment=='Positive'?'selected':'' ?>>Positive</option>
                <option value="Neutral" <?= $filter_sentiment=='Neutral'?'selected':'' ?>>Neutral</option>
                <option value="Negative" <?= $filter_sentiment=='Negative'?'selected':'' ?>>Negative</option>
                <option value="Anxious" <?= $filter_sentiment=='Anxious'?'selected':'' ?>>Anxious</option>
                <option value="Excited" <?= $filter_sentiment=='Excited'?'selected':'' ?>>Excited</option>
                <option value="Confused" <?= $filter_sentiment=='Confused'?'selected':'' ?>>Confused</option>
            </select>
        </div>

        <div class="col-md-4">
            <select onchange="applyFilter()" id="filter_category" class="form-control">
                <option value="">All Categories</option>
                <option value="Work" <?= $filter_category=='Work'?'selected':'' ?>>Work</option>
                <option value="Personal" <?= $filter_category=='Personal'?'selected':'' ?>>Personal</option>
                <option value="Health" <?= $filter_category=='Health'?'selected':'' ?>>Health</option>
                <option value="Social" <?= $filter_category=='Social'?'selected':'' ?>>Social</option>
            </select>
        </div>

        <div class="col-md-4">
            <a id="exportBtn" href="#" class="btn btn-success w-100">Export CSV</a>
        </div>
    </div>

    <!-- MOOD HISTORY -->
    <h4 class="mb-3">Mood History</h4>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Entry</th>
                    <th>Reason</th>
                    <th>Sentiment</th>
                    <th>Category</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($moods as $mood): ?>
                <tr class="<?= htmlspecialchars($mood['sentiment']) ?>">
                    <td><?= $mood['created_at'] ?></td>
                    <td><?= htmlspecialchars($mood['entry']) ?></td>
                    <td><?= htmlspecialchars($mood['reason']) ?></td>
                    <td>
                        <span class="badge 
                            <?= $mood['sentiment']=='Positive'?'bg-success':
                               ($mood['sentiment']=='Neutral'?'bg-warning':
                               ($mood['sentiment']=='Negative'?'bg-danger':'bg-info')) ?>">
                            <?= htmlspecialchars($mood['sentiment']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($mood['category']) ?></td>
                    <td>
                        <a href="edit.php?id=<?= $mood['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="delete.php?id=<?= $mood['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- MOOD STATISTICS -->
    <h4 class="mb-3">Mood Statistics</h4>
    <div class="row mb-5">
        <?php foreach (['Positive'=>$positive,'Neutral'=>$neutral,'Negative'=>$negative,'Anxious'=>$anxious,'Excited'=>$excited,'Confused'=>$confused] as $label=>$count): ?>
        <div class="col-md-2 mb-3">
            <div class="card text-center p-3 <?= $label ?>">
                <h6><?= $label ?></h6>
                <div class="progress mb-2" style="height: 25px;">
                    <div class="progress-bar" role="progressbar" style="width: <?= $percent($count) ?>%">
                        <?= $percent($count) ?>%
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
function applyFilter(){
    const sentiment = document.getElementById('filter_sentiment').value;
    const category = document.getElementById('filter_category').value;
    window.location.href = 'index.php?sentiment='+sentiment+'&category='+category;
}

document.getElementById('exportBtn').addEventListener('click', function(){
    const sentiment = document.getElementById('filter_sentiment').value;
    const category = document.getElementById('filter_category').value;
    this.href = 'export.php?sentiment='+sentiment+'&category='+category;
});
</script>

</body>
</html>
