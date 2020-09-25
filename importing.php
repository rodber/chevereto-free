<?php
/* --------------------------------------------------------------------

  This file is part of Chevereto Free.
  https://chevereto.com/free

  (c) Rodolfo Berrios <rodolfo@chevereto.com>

  For the full copyright and license information, please view the LICENSE
  file that was distributed with this source code.

  --------------------------------------------------------------------- */

define('access', 'cli');

$isCron = getenv('IS_CRON');
if (!$isCron) {
    header('HTTP/1.0 403 Forbidden');
    die("403 Forbidden\n");
}
$threadID = getenv('THREAD_ID');
if (!$threadID) {
    die("Missing thread id (int)\n");
}
if (!include_once('app/loader.php')) {
    die("Can't find app/loader.php\n");
}
$loop = 1;
do {
    try {
        CHV\Import::refresh();
        $jobs = CHV\Import::autoJobs();
        if (!$jobs) {
            echo "No jobs left.\n";
            die(0);
        }
        $id = $jobs[0]['import_id'];
        $import = new CHV\Import();
        $import->id = $id;
        $import->thread = (int) $threadID;
        $import->process();
        echo "Processed job id #$id\n";
        $loop++;
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
        die(255);
    }
} while (CHV\isSafeToExecute());
echo "--\nLooped $loop times ~ /dashboard/bulk for stats \n";
die(0);
