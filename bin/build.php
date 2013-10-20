#!/usr/bin/env php
<?php

$repo_name = 'Composer Repository';
$repo_path = 'https://s3-us-west-2.amazonaws.com/example-bucket';
$s3_bucket = 'example-bucket';
$s3_user = 's3-key';
$s3_pass = 's3-secret';
$repo_file_directory = 'files';

$repo = array(
    'name' => $repo_name,
    'homepage' => $repo_path,
    'repositories' => array(
        array('type' => 'composer', 'url' => 'http://packagist.org'),
    ),
    'archive' => array(
        'directory' => $repo_file_directory,
        'format' => 'tar',
        'prefix-url' => $repo_path,
        'skip-dev' => true,
    ),
    'config' => array(
        'notify-on-install' => false,
    ),
);



if (!isset($_SERVER['argv'][1]))
{
    echo 'No composer.lock file provided'."\r\n";
    exit(1);
}

if (substr($_SERVER['argv'][1], -5) !== '.lock') {
    echo 'File provided, "'.$_SERVER['argv'][1].'" is not a composer.lock file'."\r\n";
}

$lock_file = $_SERVER['argv'][1];

$info = json_decode(file_get_contents($lock_file));

if (!$info) {
    echo 'Lock file provided did not contain json'."\r\n";
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';

$repo_file_directory = 'files';

$base_dir = __DIR__.'/../';

if (!file_exists($base_dir.'/build')) {
    mkdir($base_dir.'/build');
}

$build_dir = $base_dir.'/build/'.$repo_file_directory;

mkdir($build_dir);
mkdir($build_dir.'/src');


$versions = array();

foreach($info->packages as $package) {
    $versions[$package->name] = $package->version;
}

$repo['require'] = $versions;

file_put_contents($build_dir.'/src/config.json', json_encode($repo));

if (!is_dir($base_dir.'/satis')) {
    passthru($base_dir.'/composer.phar create-project composer/satis --stability=dev --no-interaction');
}

passthru($base_dir.'/satis/bin/satis build '.$build_dir.'/src/config.json '.$build_dir.'/dist');

$client = Aws\S3\S3Client::factory(array(
    'key' => $s3_user,
    'secret' => $s3_pass,
));

$client->registerStreamWrapper();

if (!is_dir($build_dir.'/dist/'.$repo_file_directory)) {
    echo 'Error occurred'."\r\n";
    exit(1);
}

$bucket_base = 's3://'.$s3_bucket;

mkdir($bucket_base.'/'.$repo_file_directory);

$d = dir($build_dir.'/dist/'.$repo_file_directory);
while (false !== ($entry = $d->read()))
{
    if ($entry == '.' || $entry == '..') {
        continue;
    }

    copy($build_dir.'/dist/'.$repo_file_directory.'/'.$entry, $bucket_base.'/'.$repo_file_directory.'/'.$entry);
}

copy($build_dir.'/dist/packages.json', $bucket_base.'/packages.json');
copy($build_dir.'/dist/index.html', $bucket_base.'/index.html');