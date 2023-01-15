<?php

if (!empty($testPreparedFlag)) {
    // Thest was prepared so let's calculate resources and save the output in separate file
    $result = [
        'requestId' => $requestId,
        'excecutionTime' => microtime(true) - $startTime,
        'excecutionMemory' => memory_get_peak_usage(),
        'excecutionCPU' => sys_getloadavg()[0] - $startCPU[0],
        'dbQueries' => $wpdb->num_queries,
    ];

    file_put_contents($testOutputDir . "/$requestId.log", serialize($result));
}
