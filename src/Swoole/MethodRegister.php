<?php


namespace App\Swoole;


use App\LineAnalyzer;
use ReflectionException;
use ReflectionMethod;

class MethodRegister implements LineAnalyzer
{

    /** @var ReflectionMethod */
    public static $currentMethod = null;

    /** @var string[]  */
    private static $notFound = [];

    /** @var ReflectionMethod[] */
    private static $documentedMethods = [];

    public static function matching(string $line): bool
    {
        // PHP_METHOD(swoole_server, __construct)
        if (!preg_match('/PHP_METHOD\(([a-zA-Z\_]+), (.*)\)$/', $line, $matches)) {
            return false;
        }

        $module_name = $matches[1];
        $method_name = $matches[2];

        if (!ClassRegister::hasClassByModule($module_name)) {
            // @todo module not found in analyzer. Exception?
            return false;
        }
        $class = ClassRegister::getClassByModule($module_name);
        $class_identify = $class->getName() . '::' . $method_name;

        try {
            $currentMethod = $class->getMethod($method_name);
        } catch (ReflectionException $exception) {
            // @todo method is not fund in installed extension
            self::$notFound[] = $class_identify;
            // @todo return true will cause an exception
            return true;
        }

        self::$documentedMethods[$class_identify] = $currentMethod;
        self::$currentMethod = $currentMethod;
        return true;
    }

    /**
     * @return string[]
     */
    public static function getNotFound(): array
    {
        return self::$notFound;
    }
}