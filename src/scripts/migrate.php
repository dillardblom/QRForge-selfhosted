<?php
// Lightweight migration runner: applies any db/migrations/*.sql file not yet recorded
// in schema_migrations, in filename order. Every migration file is itself idempotent
// (checks information_schema before altering), so re-running an already-applied file
// is a safe no-op - this script leans on that instead of needing transactional rollback.
// Run automatically by the container entrypoint on every start (see docker/entrypoint.sh),
// so a `git pull` + restart is enough to bring an existing install's schema up to date -
// docker-entrypoint-initdb.d only ever runs db/init.sql, and only on a brand new volume.

require_once __DIR__ . '/../config/environment.php';

mysqli_report(MYSQLI_REPORT_OFF);

$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME, DATABASE_PORT);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "migrate.php: could not connect to database: {$mysqli->connect_error}\n");
    exit(1);
}
$mysqli->set_charset(DATABASE_CHARSET);

$mysqli->query(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        filename VARCHAR(255) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
);

$files = glob(__DIR__ . '/../db/migrations/*.sql');
sort($files, SORT_STRING);

$applied = [];
$result = $mysqli->query('SELECT filename FROM schema_migrations');
while ($row = $result->fetch_assoc()) {
    $applied[$row['filename']] = true;
}

$ran = 0;
foreach ($files as $file) {
    $filename = basename($file);
    if (isset($applied[$filename])) {
        continue;
    }

    echo "Applying migration: $filename\n";

    if (!$mysqli->multi_query(file_get_contents($file))) {
        fwrite(STDERR, "migrate.php: failed to apply $filename: {$mysqli->error}\n");
        exit(1);
    }
    // multi_query queues result sets asynchronously - drain them all before the next
    // query, and check for a mid-batch error on each one.
    do {
        if ($res = $mysqli->store_result()) {
            $res->free();
        }
        if ($mysqli->errno) {
            fwrite(STDERR, "migrate.php: error while applying $filename: {$mysqli->error}\n");
            exit(1);
        }
    } while ($mysqli->more_results() && $mysqli->next_result());

    $stmt = $mysqli->prepare('INSERT INTO schema_migrations (filename, applied_at) VALUES (?, NOW())');
    $stmt->bind_param('s', $filename);
    $stmt->execute();
    $stmt->close();

    $ran++;
}

echo $ran === 0 ? "No pending migrations.\n" : "Applied $ran migration(s).\n";

$mysqli->close();
