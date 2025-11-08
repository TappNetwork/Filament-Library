<?php

arch('will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch()->preset()->php();

arch()->preset()->security();

arch()->preset()->laravel();
