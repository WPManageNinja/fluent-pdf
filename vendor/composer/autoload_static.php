<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitaff2ea21b2b73eec92c319fdf9a4e333
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'FluentPdf\\Support\\' => 18,
            'FluentPdf\\Modules\\' => 18,
            'FluentPdf\\Classes\\' => 18,
            'FluentPdf\\API\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'FluentPdf\\Support\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Support',
        ),
        'FluentPdf\\Modules\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Modules',
        ),
        'FluentPdf\\Classes\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Classes',
        ),
        'FluentPdf\\API\\' => 
        array (
            0 => __DIR__ . '/../..' . '/API',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitaff2ea21b2b73eec92c319fdf9a4e333::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitaff2ea21b2b73eec92c319fdf9a4e333::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitaff2ea21b2b73eec92c319fdf9a4e333::$classMap;

        }, null, ClassLoader::class);
    }
}
