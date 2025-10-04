<?php
namespace WPBS\Services;

class RewriteManager
{
    public static function activate(): void
    {
        flush_rewrite_rules();
    }
}
