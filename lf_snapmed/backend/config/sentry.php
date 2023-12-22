<?php

return array(
    'dsn' => 'https://3326418c33f148ee82a0ed15d14850d0@sentry.io/1311135',
    // Getting git tag to use as release
    'release' => trim(exec('git describe --abbrev=0')),
    // capture release as git sha
    // 'release' => trim(exec('git log --pretty="%h" -n1 HEAD'))
);
