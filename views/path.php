<?php

function views_path(string $rel): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($rel, '/\\'));
}

function shared_path(string $rel): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($rel, '/\\'));
}
