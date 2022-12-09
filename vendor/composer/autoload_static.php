<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit79341161d381866b40e5c1f618f2c11f
{
    public static $prefixesPsr0 = array (
        'E' => 
        array (
            'Eluceo\\iCal' => 
            array (
                0 => __DIR__ . '/..' . '/eluceo/ical/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit79341161d381866b40e5c1f618f2c11f::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit79341161d381866b40e5c1f618f2c11f::$classMap;

        }, null, ClassLoader::class);
    }
}