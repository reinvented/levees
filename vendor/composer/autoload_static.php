<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit79341161d381866b40e5c1f618f2c11f
{
    public static $files = array (
        '6e3fae29631ef280660b3cdad06f25a8' => __DIR__ . '/..' . '/symfony/deprecation-contracts/function.php',
    );

    public static $prefixLengthsPsr4 = array (
        'E' => 
        array (
            'Eluceo\\iCal\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Eluceo\\iCal\\' => 
        array (
            0 => __DIR__ . '/..' . '/eluceo/ical/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit79341161d381866b40e5c1f618f2c11f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit79341161d381866b40e5c1f618f2c11f::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit79341161d381866b40e5c1f618f2c11f::$classMap;

        }, null, ClassLoader::class);
    }
}
