<?php

$vendors = [
  'Claroline' => [
    'ActivityToolBundle',
    'AnnouncementBundle',
    //'BundleRecorder',
    //'CoreBundle',
    'CursusBundle',
    'ForumBundle',
    'ImagePlayerBundle',
    //'InstallationBundle',
    //'KernelBundle',
    'MessageBundle',
    //'MigrationBundle',
    'PdfPlayerBundle',
    'RssReaderBundle',
    'ScormBundle',
    'SurveyBundle',
    'TeamBundle',
    'VideoPlayerBundle',
    //'WebInstaller',
    'WebResourceBundle'
  ],
  'FormaLibre' => [
    'PresenceBundle',
    'ReservationBundle',
    'SupportBundle'
  ]/*,
  'hevinci' => [
    'CompetencyBundle',
    'FavouriteBundle',
    'UrlBundle'
  ],
  'icaplyon1' => [
    'BadgeBundle',
    'BlogBundle',
    'DropzoneBundle',
    'LessonBundle',
    'PortfolioBundle',
    'SocialMediaBundle',
    'WebsiteBundle',
    'WikiBundle',
    'OauthBundle'
  ],
  'innovalangues' => [
    'PathBundle'
  ],
  'ujm-dev' => [
    'ExoBundle'
  ]*/
];

$remotes = array_map(function ($line) {
  $parts = explode("\t", $line);
  $remote = array_shift($parts);

  return $remote;
}, cmd('git remote -v'));

foreach ($vendors as $vendor => $packages) {
  foreach ($packages as $package) {
    cmd("php import.php {$vendor} {$package} plugin/" . prettify($package));
}

function cmd($cmd) {
  echo "Executing: {$cmd}\n";
  exec($cmd, $output, $code);

  if ($code !== 0) {
    die("Command failed, aborting\n");
  }

  return $output;
}

function prettify($str) {
	return str_replace('-bundle', '', ltrim(strtolower(preg_replace('/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]|[0-9]{1,}/', '-$0', $str)), '-'));
}
