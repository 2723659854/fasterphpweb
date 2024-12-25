<?php

require_once __DIR__ . '/src/ProgressBar.php';
use Xiaosongshu\Progress\ProgressBar;

$progressBar = new ProgressBar();
$progressBar->createBar(120);
for ($i = 1; $i <= 60; $i++) {
    $progressBar->advance();
}
$progressBar->finished();
