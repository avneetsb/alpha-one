<?php

declare(strict_types=1);

$cloverPath = __DIR__.'/../coverage/clover.xml';
if (! file_exists($cloverPath)) {
    fwrite(STDOUT, "No coverage file found at coverage/clover.xml. Run tests with coverage first.\n");
    exit(0);
}

$xml = new SimpleXMLElement(file_get_contents($cloverPath));

$files = [];
$totals = [
    'stmts' => 0,
    'stmts_cov' => 0,
    'cond' => 0,
    'cond_cov' => 0,
    'funcs' => 0,
    'funcs_cov' => 0,
    'lines' => 0,
    'lines_cov' => 0,
];

foreach ($xml->project->file as $file) {
    $path = (string) $file['name'];
    $metrics = $file->metrics;
    $stmts = (int) $metrics['statements'];
    $stmtsCov = (int) $metrics['coveredstatements'];
    $cond = (int) $metrics['conditionals'];
    $condCov = (int) $metrics['coveredconditionals'];
    $funcs = (int) $metrics['methods'];
    $funcsCov = (int) $metrics['coveredmethods'];

    $lines = 0;
    $linesCov = 0;
    $uncovered = [];
    $condLines = 0;
    $condLinesCov = 0;

    foreach ($file->line as $line) {
        $num = (int) $line['num'];
        $type = (string) $line['type'];
        $count = isset($line['count']) ? (int) $line['count'] : 0;
        if ($type === 'stmt') {
            $lines++;
            if ($count > 0) {
                $linesCov++;
            } else {
                $uncovered[] = $num;
            }
        } elseif ($type === 'cond') {
            $condLines++;
            $t = isset($line['truecount']) ? (int) $line['truecount'] : null;
            $f = isset($line['falsecount']) ? (int) $line['falsecount'] : null;
            $covered = false;
            if ($t !== null && $f !== null) {
                $covered = ($t > 0 && $f > 0);
            } else {
                $covered = ($count > 0);
            }
            if ($covered) {
                $condLinesCov++;
            }
        }
    }

    if ($condLines > 0) {
        $cond = $condLines;
        $condCov = $condLinesCov;
    }

    $files[] = [
        'path' => $path,
        'stmts' => $stmts,
        'stmts_cov' => $stmtsCov,
        'cond' => $cond,
        'cond_cov' => $condCov,
        'funcs' => $funcs,
        'funcs_cov' => $funcsCov,
        'lines' => $lines,
        'lines_cov' => $linesCov,
        'uncovered' => $uncovered,
    ];

    $totals['stmts'] += $stmts;
    $totals['stmts_cov'] += $stmtsCov;
    $totals['cond'] += $cond;
    $totals['cond_cov'] += $condCov;
    $totals['funcs'] += $funcs;
    $totals['funcs_cov'] += $funcsCov;
    $totals['lines'] += $lines;
    $totals['lines_cov'] += $linesCov;
}

function pct(int $cov, int $tot): string
{
    if ($tot === 0) {
        return '0.00';
    }

    return number_format(($cov / $tot) * 100, 2);
}
function pctOrNA(int $cov, int $tot): string
{
    return $tot === 0 ? 'N/A' : pct($cov, $tot);
}

// Header
$line = str_repeat('-', 130);
$cReset = "\033[0m";
$cBold = "\033[1m";
$cHead = "\033[36m";
$cGreen = "\033[32m";
$cYellow = "\033[33m";
$cRed = "\033[31m";
$cGray = "\033[90m";
fwrite(STDOUT, " $cHead$line$cReset\n");
fwrite(STDOUT, " {$cBold}File                                                        |  % Stmts | % Branch |  % Funcs |  % Lines | Uncovered Line #s {$cReset}\n");
fwrite(STDOUT, " $cHead$line$cReset\n");

foreach ($files as $f) {
    $fileName = $f['path'];
    if (strlen($fileName) > 58) {
        $fileName = '…'.substr($fileName, -57);
    }
    $uncoveredStr = (function (array $nums) {
        if (empty($nums)) {
            return '';
        }
        sort($nums);
        $ranges = [];
        $start = $nums[0];
        $prev = $nums[0];
        for ($i = 1; $i < count($nums); $i++) {
            if ($nums[$i] === $prev + 1) {
                $prev = $nums[$i];

                continue;
            }
            $ranges[] = ($start === $prev) ? (string) $start : ($start.'-'.$prev);
            $start = $nums[$i];
            $prev = $nums[$i];
        }
        $ranges[] = ($start === $prev) ? (string) $start : ($start.'-'.$prev);
        if (count($ranges) > 25) {
            $ranges = array_slice($ranges, 0, 25);
            $ranges[] = '…';
        }

        return implode(',', $ranges);
    })($f['uncovered']);

    $colorize = function (string $pctStr) use ($cGreen, $cYellow, $cRed, $cGray, $cReset) {
        if ($pctStr === 'N/A') {
            return $cGray.$pctStr.$cReset;
        }
        $val = (float) $pctStr;
        if ($val >= 80.0) {
            return $cGreen.$pctStr.$cReset;
        }
        if ($val >= 50.0) {
            return $cYellow.$pctStr.$cReset;
        }

        return $cRed.$pctStr.$cReset;
    };

    $row = sprintf(
        " %-58s | %8s | %8s | %8s | %8s | %s\n",
        $fileName,
        $colorize(pct($f['stmts_cov'], $f['stmts'])),
        $colorize(pctOrNA($f['cond_cov'], $f['cond'])),
        $colorize(pct($f['funcs_cov'], $f['funcs'])),
        $colorize(pct($f['lines_cov'], $f['lines'])),
        $uncoveredStr
    );
    fwrite(STDOUT, $row);
}

fwrite(STDOUT, " $cHead$line$cReset\n\n");
fwrite(STDOUT, " {$cBold}=============================== Coverage summary ==============================={$cReset} \n");
fwrite(STDOUT, sprintf(" Statements   : %s%% ( %d/%d ) \n", pct($totals['stmts_cov'], $totals['stmts']), $totals['stmts_cov'], $totals['stmts']));
if ($totals['cond'] === 0) {
    fwrite(STDOUT, sprintf(" Branches     : %s ( %d/%d ) \n", $cGray.'N/A'.$cReset, $totals['cond_cov'], $totals['cond']));
} else {
    fwrite(STDOUT, sprintf(" Branches     : %s%% ( %d/%d ) \n", pct($totals['cond_cov'], $totals['cond']), $totals['cond_cov'], $totals['cond']));
}
fwrite(STDOUT, sprintf(" Functions    : %s%% ( %d/%d ) \n", pct($totals['funcs_cov'], $totals['funcs']), $totals['funcs_cov'], $totals['funcs']));
fwrite(STDOUT, sprintf(" Lines        : %s%% ( %d/%d ) \n", pct($totals['lines_cov'], $totals['lines']), $totals['lines_cov'], $totals['lines']));
fwrite(STDOUT, " {$cBold}==============================================================================={$cReset}\n");
