<?php

// Load the Google API PHP Client Library.
require_once __DIR__ . '/vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/client_secrets.json');
$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);


// If the user has already authorized this app then get an access token
// else redirect to ask the user to authorize access to Google Analytics.
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  // Set the access token on the client.
  $client->setAccessToken($_SESSION['access_token']);

  $sesString = "Количество посещений сегодня";
  $sesString7 = "Количество посещений за 7д";
  $sesString30 = "Количество посещений за 30д";

  // Create an authorized analytics service object.
  $analytics = new Google_Service_AnalyticsReporting($client);
  $startdate = "today";
  $enddate = "today";

  $startdate7 = "7daysAgo";
  $enddate7 = "today";

  $startdate30 = "30daysAgo";
  $enddate30 = "today";

  // Call the Analytics Reporting API V4.
  $response = getReport($analytics, $startdate, $enddate, $sesString);
  $response2 = getReport($analytics, $startdate7, $enddate7, $sesString7);
  $response3 = getReport($analytics, $startdate30, $enddate30, $sesString30);

  // Print the response.
  printResults($response);
  printResults($response2);
  printResults($response3);

} else {
  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}


/**
 * Queries the Analytics Reporting API V4.
 *
 * @param service An authorized Analytics Reporting API V4 service object.
 * @return The Analytics Reporting API V4 response.
 */
function getReport($analytics, $startdate, $enddate, $sesString) {

  // Replace with your view ID, for example XXXX.
  $VIEW_ID = "189503242";

  // Create the DateRange object.(for 1 day)
  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
  $dateRange->setStartDate($startdate);
  $dateRange->setEndDate($enddate);

  // Create the Metrics object.
  $sessions = new Google_Service_AnalyticsReporting_Metric();
  $sessions->setExpression("ga:sessions");
  $sessions->setAlias($sesString);


  // Create the ReportRequest object.
  $request = new Google_Service_AnalyticsReporting_ReportRequest();
  $request->setViewId($VIEW_ID);
  $request->setDateRanges(array($dateRange));
  $request->setMetrics(array($sessions));

  $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
  $body->setReportRequests( array( $request) );
  
  return $analytics->reports->batchGet( $body );
}


/**
 * Parses and prints the Analytics Reporting API V4 response.
 *
 * @param An Analytics Reporting API V4 response.
 */
function printResults($reports) {
  for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
    $report = $reports[ $reportIndex ];
    $header = $report->getColumnHeader();
    $dimensionHeaders = $header->getDimensions();
    $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
    $rows = $report->getData()->getRows();

    for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
      $row = $rows[ $rowIndex ];
      $dimensions = $row->getDimensions();
      $metrics = $row->getMetrics();
      for ($i = 0; $dimensionHeaders != null && $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
        print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
      }

      for ($j = 0; $j < count($metrics); $j++) {
        $values = $metrics[$j]->getValues();
        for ($k = 0; $k < count($values); $k++) {
          $entry = $metricHeaders[$k];
          print($entry->getName() . ": " . $values[$k] . "\n");
        }
      }
    }
  }
}

