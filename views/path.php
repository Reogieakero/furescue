<?php

function views_path(string $rel): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($rel, '/\\'));
}
