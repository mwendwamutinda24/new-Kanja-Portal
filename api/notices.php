<?php
// ============================================================
// notices.php — Notice Board REST API
// Stephen Kanja School Management System
// ============================================================
// Endpoints (all return JSON):
//   GET    notices.php                       → list all notices + counts
//   GET    notices.php?filter=urgent|info|event  → filter by type
//   GET    notices.php?id=5                  → single notice
//   POST   notices.php   {title,type,posted_by}   → create
//   PUT    notices.php?id=5 {title,type,posted_by} → update
//   DELETE notices.php?id=5                  → delete
// ============================================================

// ── Headers ─────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── DB connection ───────────────────────────────────────────
// Uses the same conn.php as the rest of the portal. Make sure
// conn.php defines $conn as a valid mysqli connection.
mysqli_report(MYSQLI_REPORT_OFF);

try {
    include __DIR__ . '/conn.php';
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection unavailable.']);
    exit;
}

// ── Auto-create the notices table ───────────────────────────
// Safe to run on every request — CREATE TABLE IF NOT EXISTS
// is a no-op when the table already exists.
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS notices (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        title      VARCHAR(500) NOT NULL,
        type       ENUM('info','urgent','event') NOT NULL DEFAULT 'info',
        posted_by  VARCHAR(100) DEFAULT 'Admin',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (type),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Helpers ─────────────────────────────────────────────────
function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return $_POST;
    }
    $json = json_decode($raw, true);
    return is_array($json) ? $json : $_POST;
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 0)       return 'just now';
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff / 60)    . 'm ago';
    if ($diff < 86400)   return floor($diff / 3600)  . 'h ago';
    if ($diff < 604800)  return floor($diff / 86400) . 'd ago';
    return date('d M Y', strtotime($datetime));
}

function row_to_notice(array $r): array {
    return [
        'id'         => (int)$r['id'],
        'title'      => (string)$r['title'],
        'type'       => (string)$r['type'],
        'posted_by'  => (string)$r['posted_by'],
        'created_at' => (string)$r['created_at'],
        'time_ago'   => time_ago((string)$r['created_at']),
    ];
}

function valid_type(string $t): bool {
    return in_array($t, ['info', 'urgent', 'event'], true);
}

// ── Router ──────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

switch ($method) {

    // ══════════════════ GET ══════════════════
    case 'GET':
        // Single notice by id
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT * FROM notices WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                json_out(['success' => false, 'error' => 'Notice not found'], 404);
            }
            json_out(['success' => true, 'data' => row_to_notice($row)]);
        }

        // List with optional filter
        $filter = $_GET['filter'] ?? '';
        $where  = '';
        $params = [];
        $types  = '';

        if (valid_type($filter)) {
            $where  = "WHERE type = ?";
            $params[] = $filter;
            $types   = 's';
        }

        $sql = "SELECT * FROM notices $where ORDER BY created_at DESC, id DESC";
        if ($params) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = mysqli_query($conn, $sql);
        }

        $data = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $data[] = row_to_notice($r);
            }
        }
        if (isset($stmt)) $stmt->close();

        // Type counts for the filter chips
        $counts = ['all' => 0, 'info' => 0, 'urgent' => 0, 'event' => 0];
        $c = mysqli_query($conn, "SELECT type, COUNT(*) AS c FROM notices GROUP BY type");
        if ($c) {
            while ($r = $c->fetch_assoc()) {
                if (isset($counts[$r['type']])) {
                    $counts[$r['type']] = (int)$r['c'];
                    $counts['all']     += (int)$r['c'];
                }
            }
        }

        json_out([
            'success' => true,
            'count'   => count($data),
            'counts'  => $counts,
            'data'    => $data,
        ]);

    // ══════════════════ POST ══════════════════
    case 'POST':
        $in = read_body();

        $title     = trim((string)($in['title']     ?? ''));
        $typeRaw   = trim((string)($in['type']      ?? 'info'));
        $postedBy  = trim((string)($in['posted_by'] ?? 'Admin'));

        if ($title === '') {
            json_out(['success' => false, 'error' => 'Notice title cannot be empty.'], 422);
        }
        if (mb_strlen($title) > 500) {
            json_out(['success' => false, 'error' => 'Notice title is too long (max 500 characters).'], 422);
        }
        if (!valid_type($typeRaw)) {
            $typeRaw = 'info';
        }
        if ($postedBy === '') {
            $postedBy = 'Admin';
        }
        if (mb_strlen($postedBy) > 100) {
            $postedBy = mb_substr($postedBy, 0, 100);
        }

        $stmt = $conn->prepare(
            "INSERT INTO notices (title, type, posted_by, created_at) VALUES (?, ?, ?, NOW())"
        );
        $stmt->bind_param('sss', $title, $typeRaw, $postedBy);

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            json_out(['success' => false, 'error' => 'Could not save notice: ' . $err], 500);
        }
        $newId = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        json_out([
            'success' => true,
            'message' => 'Notice posted successfully.',
            'data'    => row_to_notice($row),
        ], 201);

    // ══════════════════ PUT ══════════════════
    case 'PUT':
        if ($id <= 0) {
            json_out(['success' => false, 'error' => 'id query parameter is required.'], 400);
        }

        $in = read_body();

        $sets   = [];
        $params = [];
        $types  = '';

        if (array_key_exists('title', $in)) {
            $t = trim((string)$in['title']);
            if ($t === '') {
                json_out(['success' => false, 'error' => 'Notice title cannot be empty.'], 422);
            }
            if (mb_strlen($t) > 500) {
                json_out(['success' => false, 'error' => 'Notice title is too long.'], 422);
            }
            $sets[]   = "title = ?";
            $params[] = $t;
            $types   .= 's';
        }

        if (array_key_exists('type', $in)) {
            $ty = (string)$in['type'];
            if (!valid_type($ty)) {
                json_out(['success' => false, 'error' => 'Invalid notice type.'], 422);
            }
            $sets[]   = "type = ?";
            $params[] = $ty;
            $types   .= 's';
        }

        if (array_key_exists('posted_by', $in)) {
            $p = trim((string)$in['posted_by']);
            if ($p === '') $p = 'Admin';
            if (mb_strlen($p) > 100) $p = mb_substr($p, 0, 100);
            $sets[]   = "posted_by = ?";
            $params[] = $p;
            $types   .= 's';
        }

        if (!$sets) {
            json_out(['success' => false, 'error' => 'Nothing to update.'], 422);
        }

        // Confirm the row exists
        $check = $conn->prepare("SELECT id FROM notices WHERE id = ? LIMIT 1");
        $check->bind_param('i', $id);
        $check->execute();
        $exists = (bool)$check->get_result()->fetch_assoc();
        $check->close();

        if (!$exists) {
            json_out(['success' => false, 'error' => 'Notice not found.'], 404);
        }

        $params[] = $id;
        $types   .= 'i';

        $sql  = "UPDATE notices SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            json_out(['success' => false, 'error' => 'Could not update notice: ' . $err], 500);
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        json_out([
            'success' => true,
            'message' => 'Notice updated.',
            'data'    => row_to_notice($row),
        ]);

    // ══════════════════ DELETE ══════════════════
    case 'DELETE':
        if ($id <= 0) {
            json_out(['success' => false, 'error' => 'id query parameter is required.'], 400);
        }

        $stmt = $conn->prepare("DELETE FROM notices WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            json_out(['success' => false, 'error' => 'Could not delete notice: ' . $err], 500);
        }

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            json_out(['success' => false, 'error' => 'Notice not found.'], 404);
        }
        $stmt->close();

        json_out(['success' => true, 'message' => 'Notice deleted.']);

    // ══════════════════ Other ══════════════════
    default:
        json_out(['success' => false, 'error' => 'Method not allowed.'], 405);
}

mysqli_close($conn);
