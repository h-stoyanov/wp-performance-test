<?php
$allHeaders = getallheaders();

if (isset($allHeaders['X-Custom-Request-Id'])) {
    // setup global variables
    global $testOutputDir;
    global $testPreparedFlag;
    global $startTime;
    global $requestId;
    global $startCPU;

    $testOutputDir = __DIR__ . '/tmp';
    $testPreparedFlag = true;
    $requestId = intval($allHeaders['X-Custom-Request-Id']);
    // Measure start time
    $startTime = microtime(true);

    // Measure the starting CPU usage
    $startCPU = sys_getloadavg();
}
