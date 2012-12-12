<?php
namespace ActiveMongo2\Runtime;

class Events
{
    private static function dispatch($action, $class, $object, Array $args)
    {
        /** run events defined in plugins */
        $classAnn = $class->getAnnotations();
        $plugargs = array_merge(array(null, $object), $args);
        foreach ($classAnn as $annotation) {
            $pclass = $annotation['method'][0] == '/' 
                ? $annotation['method']
                : '\\ActiveMongo2\\Plugin\\' . $annotation['method'];

            if (Utils::class_exists($pclass)) {
                $plugin = Utils::getReflectionClass($pclass);
                foreach ($plugin->getMethods() as $method) {
                    $ann = $method->getAnnotations();
                    if (!$method->isStatic() || !$ann->has($action)) {
                        continue;
                    }

                    $plugargs[0] = $annotation['args'] ?: array();
                    call_user_func_array(array($pclass, $method->getName()), $plugargs);
                }
            }
        }

        /** run events defined in the class */
        foreach ($class->getMethods() as $method) {
            $ann = $method->getAnnotations();
            if ($method->isStatic() || !$ann->has($action)) {
                continue;
            }

            call_user_func_array(array($object, $method->getName()), $args);
        }
    }

    public static function run($action, $object, $args = array())
    {
        $class = Utils::getReflectionClass($object);
        self::dispatch($action, $class, $object, $args);

        while ($class = $class->getParentClass()) {
            self::dispatch($action, $class, $object, $args);
        }
    }
}
