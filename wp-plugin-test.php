<?php

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define('WP_USE_THEMES', false);
define('WP_DEBUG', true);

define('TEST_RESULT_OUTPUT_DIR', __DIR__ . '/tmp');

if (!is_dir(TEST_RESULT_OUTPUT_DIR)) {
    mkdir(TEST_RESULT_OUTPUT_DIR);
}

/** Loads the WordPress Environment and Template */
include __DIR__ . '/wp-blog-header.php';
include_once __DIR__ . '/wp-admin/includes/plugin.php';

$all_plugins = get_plugins();

// Parse and validate the number of requests to perform
$numberOfRequestsToPefrorm = (empty($_POST['requests-number']) || !is_numeric($_POST['requests-number'])) ? 100 : (int) $_POST['requests-number'];

// Parse the page path
$pagePath = (empty($_POST['page-path']) || !is_string($_POST['page-path'])) ? '' : $_POST['page-path'];

// Create a dropdown select with all plugins
echo '<form method="post">';
echo '<label for="plugins">Select a plugin to test:</label>';
echo '<select name="selected_plugin" id="plugins">';
foreach ($all_plugins as $key => $value) {
    $selected = '';
    if (!empty($_POST['selected_plugin']) && $key == $_POST['selected_plugin']) {
        $selected = ' selected="selected"';
    }
    echo '<option value="' . $key . '"' . $selected . '>' . $value['Name'] . '</option>';
}
echo '</select>';
echo '<label for="requests-number">Number of requests to perform:</label>';
echo '<input type="number" id="requests-number" name="requests-number" min="10" max="1000" step="1" value="' . $numberOfRequestsToPefrorm . '">';
echo '<label for="page-path">Page path to load:</label>';
echo '<input type="text" id="page-path" name="page-path" value="' . $pagePath . '">';
echo '<input type="submit" name="submit" value="Submit">';
echo '</form>';

// Check if form is submitted
if (isset($_POST['submit'])) {

    // Use this only if you have big Big BIG ... muscle!
    set_time_limit(0);
    ignore_user_abort(true);
    ini_set('memory_limit', '-1');

    $selected_plugin = $_POST['selected_plugin']; // The name of the plugin to test

    $url = home_url() . $pagePath;

    // Save the plugin active state
    $wasThePluginActive = false;

    // Deactivate the plugin if is already active
    if (is_plugin_active($selected_plugin)) {
        $wasThePluginActive = true;
        deactivate_plugins($selected_plugin);
    }

    // Prepeare a list of requests
    $requests = [];
    for ($reqId = 0; $reqId < $numberOfRequestsToPefrorm; $reqId++) {
        $requests[$reqId] = [
            'url' => $url,
            'headers' => [
                'X-Custom-Request-Id' => $reqId,
            ]
        ];
    }

    // Run test with plugin deactivated
    $deactivatedPluginResults = performRequestsAndParseResults($requests);

    // Activate the plugin
    activate_plugin($selected_plugin);

    // Run test with plugin activated
    $activatedPluginResults = performRequestsAndParseResults($requests);

    // Deactivate the plugin if it wasn't active before the execution
    if (!$wasThePluginActive) {
        deactivate_plugins($selected_plugin);
    }

    // Calculate failed and successful queries
    $deactivatedPluginFailedRequests = count(array_column($deactivatedPluginResults, 'requestFailed'));
    $deactivatedPluginSuccessfulRequests = count(array_column($deactivatedPluginResults, 'requestSuccessful'));
    $deactivatedPluginFailedRequestsRatio = $deactivatedPluginFailedRequests / $numberOfRequestsToPefrorm;
    $deactivatedPluginSuccessfulRequestsRatio = $deactivatedPluginSuccessfulRequests / $numberOfRequestsToPefrorm;

    $activatedPluginFailedRequests = count(array_column($activatedPluginResults, 'requestFailed'));
    $activatedPluginSuccessfulRequests = count(array_column($activatedPluginResults, 'requestSuccessful'));

    $activatedPluginFailedRequestsRatio = $activatedPluginFailedRequests / $numberOfRequestsToPefrorm;
    $activatedPluginSuccessfulRequestsRatio = $activatedPluginSuccessfulRequests / $numberOfRequestsToPefrorm;

    // Calculate min values
    $deactivatedPluginMinCpuUsage = min(array_column($deactivatedPluginResults, 'excecutionCPU'));
    $deactivatedPluginMinTime =  min(array_column($deactivatedPluginResults, 'excecutionTime'));
    $deactivatedPluginMinMemory = min(array_column($deactivatedPluginResults, 'excecutionMemory'));
    $deactivatedPluginMinDbQueries = min(array_column($deactivatedPluginResults, 'dbQueries'));

    $activatedPluginMinCpuUsage = min(array_column($activatedPluginResults, 'excecutionCPU'));
    $activatedPluginMinTime = min(array_column($activatedPluginResults, 'excecutionTime'));
    $activatedPluginMinMemory = min(array_column($activatedPluginResults, 'excecutionMemory'));
    $activatedPluginMinDbQueries = min(array_column($activatedPluginResults, 'dbQueries'));

    // Calculate average values
    $deactivatedPluginAvgCpuUsage = array_sum(array_column($deactivatedPluginResults, 'excecutionCPU'))  / count(array_column($deactivatedPluginResults, 'excecutionCPU'));
    $deactivatedPluginAvgTime = array_sum(array_column($deactivatedPluginResults, 'excecutionTime'))  / count(array_column($deactivatedPluginResults, 'excecutionTime'));
    $deactivatedPluginAvgMemory = array_sum(array_column($deactivatedPluginResults, 'excecutionMemory'))  / count(array_column($deactivatedPluginResults, 'excecutionMemory'));
    $deactivatedPluginAvgDbQueries = array_sum(array_column($deactivatedPluginResults, 'dbQueries'))  / count(array_column($deactivatedPluginResults, 'dbQueries'));

    $activatedPluginAvgCpuUsage = array_sum(array_column($activatedPluginResults, 'excecutionCPU'))  / count(array_column($activatedPluginResults, 'excecutionCPU'));
    $activatedPluginAvgTime = array_sum(array_column($activatedPluginResults, 'excecutionTime'))  / count(array_column($activatedPluginResults, 'excecutionTime'));
    $activatedPluginAvgMemory = array_sum(array_column($activatedPluginResults, 'excecutionMemory'))  / count(array_column($activatedPluginResults, 'excecutionMemory'));
    $activatedPluginAvgDbQueries = array_sum(array_column($activatedPluginResults, 'dbQueries'))  / count($activatedPluginResults);

    // Calculate max values
    $deactivatedPluginMaxCpuUsage = max(array_column($deactivatedPluginResults, 'excecutionCPU'));
    $deactivatedPluginMaxTime = max(array_column($deactivatedPluginResults, 'excecutionTime'));
    $deactivatedPluginMaxMemory = max(array_column($deactivatedPluginResults, 'excecutionMemory'));
    $deactivatedPluginMaxDbQueries = max(array_column($deactivatedPluginResults, 'dbQueries'));

    $activatedPluginMaxCpuUsage = max(array_column($activatedPluginResults, 'excecutionCPU'));
    $activatedPluginMaxTime = max(array_column($activatedPluginResults, 'excecutionTime'));
    $activatedPluginMaxMemory = max(array_column($activatedPluginResults, 'excecutionMemory'));
    $activatedPluginMaxDbQueries = max(array_column($activatedPluginResults, 'dbQueries'));

    // Prepare the output

    $output = "<h1>Performance Test Report</h1>";

    $output .= "<h2>Test Results</h2>";
    $output .= "<h3>Plugin: $selected_plugin</h3>";
    $output .= "<h3>URL: $url</h3>";
    $output .= "<h2>Plugin status: Deactivated</h2>";
    $output .= "<table>";

    $output .= "<tr><th>Failed Requests</th><td>$deactivatedPluginFailedRequests</td></tr>";
    $output .= "<tr><th>Successful Requests</th><td>$deactivatedPluginSuccessfulRequests</td></tr>";
    $output .= "<tr><th>Failed / Successful Requests Ratio</th><td>$deactivatedPluginFailedRequestsRatio / $deactivatedPluginSuccessfulRequestsRatio</td></tr>";

    $output .= "<tr><th>Min Response Time</th><td>$deactivatedPluginMinTime seconds</td></tr>";
    $output .= "<tr><th>Avg Response Time</th><td>$deactivatedPluginAvgTime seconds</td></tr>";
    $output .= "<tr><th>Max Response Time</th><td>$deactivatedPluginMaxTime seconds</td></tr>";

    $output .= "<tr><th>Min CPU Usage</th><td>$deactivatedPluginMinCpuUsage</td></tr>";
    $output .= "<tr><th>Avg CPU Usage</th><td>$deactivatedPluginAvgCpuUsage</td></tr>";
    $output .= "<tr><th>Max CPU Usage</th><td>$deactivatedPluginMaxCpuUsage</td></tr>";

    $output .= "<tr><th>Min Memory Usage</th><td>" . number_format($deactivatedPluginMinMemory / 1024, 2) . " KB</td></tr>";
    $output .= "<tr><th>Avg Memory Usage</th><td>" . number_format($deactivatedPluginAvgMemory / 1024, 2) . " KB</td></tr>";
    $output .= "<tr><th>Max Memory Usage</th><td>" . number_format($deactivatedPluginMaxMemory / 1024, 2) . " KB</td></tr>";

    $output .= "<tr><th>Min Database Queries</th><td>$deactivatedPluginMinDbQueries</td></tr>";
    $output .= "<tr><th>Avg Database Queries</th><td>$deactivatedPluginAvgDbQueries</td></tr>";
    $output .= "<tr><th>Max Database Queries</th><td>$deactivatedPluginMaxDbQueries</td></tr>";

    $output .= "</table>";
    $output .= "<table>";

    $output .= "<h2>Plugin status: Activated</h2>";

    $output .= "<tr><th>Failed Requests</th><td>$activatedPluginFailedRequests</td></tr>";
    $output .= "<tr><th>Successful Requests</th><td>$activatedPluginSuccessfulRequests</td></tr>";
    $output .= "<tr><th>Failed / Successful Requests Ratio</th><td>$activatedPluginFailedRequestsRatio / $activatedPluginSuccessfulRequestsRatio</td></tr>";

    $output .= "<tr><th>Min Response Time</th><td>$activatedPluginMinTime seconds</td></tr>";
    $output .= "<tr><th>Avg Response Time</th><td>$activatedPluginAvgTime seconds</td></tr>";
    $output .= "<tr><th>Max Response Time</th><td>$activatedPluginMaxTime seconds</td></tr>";

    $output .= "<tr><th>Min CPU Usage</th><td>$activatedPluginMinCpuUsage</td></tr>";
    $output .= "<tr><th>Avg CPU Usage</th><td>$activatedPluginAvgCpuUsage</td></tr>";
    $output .= "<tr><th>Max CPU Usage</th><td>$activatedPluginMaxCpuUsage</td></tr>";

    $output .= "<tr><th>Min Memory Usage</th><td>" . number_format($activatedPluginMinMemory / 1024, 2) . " KB</td></tr>";
    $output .= "<tr><th>Avg Memory Usage</th><td>" . number_format($activatedPluginAvgMemory / 1024, 2) . " KB</td></tr>";
    $output .= "<tr><th>Max Memory Usage</th><td>" . number_format($activatedPluginMaxMemory / 1024, 2) . " KB</td></tr>";

    $output .= "<tr><th>Min Database Queries</th><td>$activatedPluginMinDbQueries</td></tr>";
    $output .= "<tr><th>Avg Database Queries</th><td>$activatedPluginAvgDbQueries</td></tr>";
    $output .= "<tr><th>Max Database Queries</th><td>$activatedPluginMaxDbQueries</td></tr>";

    $output .= "</table>";

    // Output the results to the browser
    echo $output;

    // Enable this if we want to save the output of the test as html file
    $saveOutput = true;

    $theTime = time();
    if ($saveOutput && file_put_contents(__DIR__ . "/wp-plugin-test-result-{$theTime}.html", $output)) {
        echo "<br/>The report is saved to '/wp-plugin-test-result-{$theTime}.html' file";
    }
}


function performRequestsAndParseResults($requests)
{
    $requestsExecutionResult = [];
    Requests::request_multiple($requests, [
        'complete' =>
        function ($responseOrResponseException, $requestId) use (&$requestsExecutionResult) {
            $currentRequestStats = [
                'requestFailed' => true,
            ];

            if (
                !is_subclass_of($responseOrResponseException, 'Requests_Exception')
                || !get_class($responseOrResponseException) == 'Requests_Exception'
            ) {
                $currentRequestStats = [
                    'requestSuccessful' => true,
                ];
            }

            $requestResult = $currentRequestStats;
            $currentFilePath = TEST_RESULT_OUTPUT_DIR . "/$requestId.log";
            if (file_exists($currentFilePath) && is_readable($currentFilePath)) {
                $currentRequestExecutionResult = unserialize(file_get_contents($currentFilePath));
                if ($currentRequestExecutionResult) {
                    $requestResult = array_merge($currentRequestStats, $currentRequestExecutionResult);
                    unlink($currentFilePath);
                } else {
                    $failedPath = TEST_RESULT_OUTPUT_DIR . "/failed";
                    if (!is_dir($failedPath)) {
                        mkdir($failedPath);
                    }

                    rename($currentFilePath, $failedPath . "/$requestId.log");
                }
            }

            $requestsExecutionResult[$requestId] = $requestResult;
        }
    ]);

    return $requestsExecutionResult;
}

if (is_dir(TEST_RESULT_OUTPUT_DIR)) {
    rmdir(TEST_RESULT_OUTPUT_DIR);
}
