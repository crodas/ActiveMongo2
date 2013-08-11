<?php
/**
 *  This file was generated with crodas/SimpleView (https://github.com/crodas/SimpleView)
 *  Do not edit this file.
 *
 */

namespace {

    class base_template_46ff768978a6897199daa478860b8cd25af655b1
    {
        protected $parent;
        protected $child;
        protected $context;

        public function yield_parent($name, $args)
        {
            $method = "section_" . sha1($name);

            if (is_callable(array($this->parent, $method))) {
                $this->parent->$method(array_merge($this->context, $args));
                return true;
            }

            if ($this->parent) {
                return $this->parent->yield_parent($name, $args);
            }

            return false;
        }

        public function do_yield($name, Array $args = array())
        {
            if ($this->child) {
                // We have a children template, we are their base
                // so let's see if they have implemented by any change
                // this section
                if ($this->child->do_yield($name, $args)) {
                    // yes!
                    return true;
                }
            }

            // Do I have this section defined?
            $method = "section_" . sha1($name);
            if (is_callable(array($this, $method))) {
                // Yes!
                $this->$method(array_merge($this->context, $args));
                return true;
            }

            // No :-(
            return false;
        }

    }

    /** 
     *  Template class generated from Documents.tpl.php
     */
    class class_4c3d011cafbc519bc12f3ed430a4e169ad8b5e8b extends base_template_46ff768978a6897199daa478860b8cd25af655b1
    {

        public function render(Array $vars = array(), $return = false)
        {
            $this->context = $vars;

            extract($vars);
            if ($return) {
                ob_start();
            }
            echo "<?php\n\nnamespace ActiveMongo2\\Generated" . ($namespace) . ";\n\nuse ActiveMongo2\\Connection;\n\nclass Mapper\n{\n    protected \$mapper = " . ( var_export($mapper, true) ) . ";\n    protected \$class_mapper = " . ( var_export($class_mapper, true) ) . ";\n    protected \$loaded = array();\n    protected \$connection;\n\n    public function __construct(Connection \$conn)\n    {\n        \$this->connection = \$conn;\n    }\n\n    public function mapCollection(\$col)\n    {\n        if (empty(\$this->mapper[\$col])) {\n            throw new \\RuntimeException(\"Cannot map {\$col} collection to its class\");\n        }\n\n        \$data = \$this->mapper[\$col];\n\n        if (empty(\$this->loaded[\$data['file']])) {\n            require_once \$data['file'];\n            \$this->loaded[\$data['file']] = true;\n        }\n\n        return \$data;\n    }\n\n    public function mapClass(\$class)\n    {\n        if (empty(\$this->class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        \$data = \$this->class_mapper[\$class];\n\n        if (empty(\$this->loaded[\$data['file']])) {\n            require_once \$data['file'];\n            \$this->loaded[\$data['file']] = true;\n        }\n\n        return \$data;\n    }\n\n    public function mapObject(\$object)\n    {\n        \$class = get_class(\$object);\n        if (empty(\$this->class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        return \$this->class_mapper[\$class];\n    }\n\n    public function validate(\$object)\n    {\n        \$class = get_class(\$object);\n        if (empty(\$this->class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        return \$this->{\"validate_\" . sha1(\$class)}(\$object);\n    }\n\n    public function populate(\$object, Array \$data)\n    {\n        \$class = get_class(\$object);\n        if (empty(\$this->class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        return \$this->{\"populate_\" . sha1(\$class)}(\$object, \$data);\n    }\n\n    public function trigger(\$event, \$object, Array \$args = array())\n    {\n        \$class  = get_class(\$object);\n        \$method = \"event_{\$event}_\" . sha1(\$class);\n        if (!is_callable(array(\$this, \$method))) {\n            throw new \\RuntimeException(\"Cannot trigger {\$event} event on '\$class' objects\");\n        }\n\n        return \$this->\$method(\$object, \$args);\n    }\n\n    public function ensureIndex(\$db)\n    {\n";
            foreach($indexes as $index) {
                echo "            \$db->" . ($index[0]) . "->ensureIndex(" . (var_export($index[1], true)) . ", " . (var_export($index[2], true)) . ");\n";
            }
            echo "    }\n\n";
            foreach($docs as $doc) {
                echo "    /**\n     *  Populate objects " . ($doc['class']) . " \n     */\n    public function populate_" . (sha1($doc['class'])) . "(\\" . ($doc['class']) . " \$object, Array \$data)\n    {\n";
                foreach($doc['annotation']->getProperties() as $prop) {
                    $name = $prop['property'];
                    if ($prop->has('Id')) {
                        $name = '_id';
                    }
                    echo "            if (array_key_exists(\"" . ($name) . "\", \$data)) {\n";
                    if (in_array('public', $prop['visibility'])) {
                        echo "                \$object->" . ($prop['property']) . " = \$data['" . ($name) . "'];\n";
                    }
                    else {
                        echo "                \$property = new \\ReflectionProperty(\$object, \"" . ( $prop['property'] ) . "\");\n                \$property->setAccessible(true);\n                \$property->setValue(\$object, \$data['" . ($name) . "']);\n";
                    }
                    echo "            }\n";
                }
                echo "    }\n\n    /**\n     *  Validate " . ($doc['class']) . " object\n     */\n    public function validate_" . (sha1($doc['class'])) . "(\\" . ($doc['class']) . " \$object)\n    {\n        \$doc = array();\n";
                foreach($doc['annotation']->getProperties() as $prop) {
                    echo "            /** " . ($prop['property']) . " */\n";
                    $propname = $prop['property'];
                    if ($prop->has('Id')) {
                        $propname = '_id';
                    }
                    if (in_array('public', $prop['visibility'])) {
                        echo "                if (\$object->" . ($prop['property']) . " !== NULL) {\n                    \$data = \$doc[\"" . ($propname) . "\"] = \$object->" . ($prop['property']) . ";\n                } else {\n";
                        if ($prop->has('Required')) {
                            echo "                        throw new \\RuntimeException(\"{\$prop['property']} cannot be empty\");\n";
                        }
                        else {
                            echo "                        \$data = NULL;\n";
                        }
                        echo "                }\n";
                    }
                    else {
                        echo "                \$property = new \\ReflectionProperty(\$object, \"" . ( $prop['property'] ) . "\");\n                \$property->setAccessible(true);\n                \$data = \$doc[\"" . ($propname) . "\"] = \$property->getValue(\$object);\n";
                        if ($prop->has('Required')) {
                            echo "                    if (\$data === NULL) {\n                        throw new \\RuntimeException(\"{\$prop['property']} cannot be empty\");\n                    }\n";
                        }
                    }
                    echo "\n";
                    foreach($validators as $name => $callback) {
                        if ($prop->has($name)) {
                            echo "                    if (empty(\$this->loaded['" . ($files[$name]) . "'])) {\n                        require_once '" . ($files[$name]) . "';\n                        \$this->loaded['" . ($files[$name]) . "'] = true;\n                    }\n                    if (\$data !== NULL && !" . ($callback) . "(\$data)) {\n                        throw new \\RuntimeException(\"Validation failed for " . ($name) . "\");\n                    }\n";
                        }
                    }
                }
                echo "\n        return \$doc;\n    }\n\n";
                foreach($events as $ev) {
                    echo "    /**\n     *  Code for " . ($ev) . " events for objects " . ($doc['class']) . "\n     */\n        protected function event_" . ($ev) . "_" . (sha1($doc['class'])) . "(\\" . ($doc['class']) . " \$document, Array \$args)\n        {\n";
                    foreach($doc['annotation']->getMethods() as $method) {
                        if ($method->has($ev)) {
                            if (in_array('public', $method['visibility'])) {
                                echo "                        \$return = \$document->" . ($method['function']) . "(\$document, \$args, \$this->connection, " . (var_export($method[0]['args'], true)) . ");\n";
                            }
                            else {
                                echo "                        \$reflection = new ReflectionMethod(\"\\\\" . (addslashes($doc['class'])) . "\", \"" . ($method['function']) . "\");\n                        \$return = \$reflection->invoke(\$document, \$document, \$args, \$this->connection, " . (var_export($method[0]['args'], true)) . ");\n";
                            }
                            echo "                    if (\$return === FALSE) {\n                        throw new \\RuntimeException(\"" . (addslashes($doc['class']) . "::" . $method['function']) . " returned false\");\n                    }\n";
                        }
                    }
                    echo "        }\n    \n";
                }
                echo "\n";
            }
            echo "}\n";

            if ($return) {
                return ob_get_clean();
            }

        }
    }

}

namespace ActiveMongo2 {

    class Templates
    {
        public static function exec($name, Array $context = array(), Array $global = array())
        {
            $tpl = self::get($name);
            return $tpl->render(array_merge($global, $context));
        }

        public static function get($name, Array $context = array())
        {
            static $classes = array (
                'documents.tpl.php' => 'class_4c3d011cafbc519bc12f3ed430a4e169ad8b5e8b',
                'documents' => 'class_4c3d011cafbc519bc12f3ed430a4e169ad8b5e8b',
            );
            $name = strtolower($name);
            if (empty($classes[$name])) {
                throw new \RuntimeException("Cannot find template $name");
            }

            $class = "\\" . $classes[$name];
            return new $class;
        }
    }

}
