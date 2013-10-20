# Satis Repository Builder

Take your composer.lock file and build a Satis repository up in S3 easily!

## Getting Started

    curl -sS https://getcomposer.org/installer | php
    ./composer.phar install
    php composer.phar create-project composer/satis --stability=dev --keep-vcs satis

Edit your `bin/build.php` to set your S3 information.

Yes, that needs to be changed. I've been waiting 4 months for me to fix it. Not looking likely! :D

Now you just run `./bin/build.php /path/to/your/composer.lock` and it does the rest!

## Issues

There's some S3 permissions issues that will come up, which someone much smarter than I about AWS
can probably give about 2 seconds of input and fix.
