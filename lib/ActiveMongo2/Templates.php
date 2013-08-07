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
            echo "<?php\n\nnamespace ActiveMongo2\\Generated" . ($namespace) . ";\n\nclass Mapper\n{\n    protected \$mapper = " . ( var_export($docs, true) ) . ";\n    protected \$class_mapper = " . ( var_export($class_mapper, true) ) . ";\n\n    public function mapCollection(\$col)\n    {\n        if (empty(self::\$mapper[\$col])) {\n            throw new \\RuntimeException(\"Cannot collection {\$col} to its collection\");\n        }\n\n        return self::\$mapper[\$col];\n    }\n\n    public function mapClass(\$class)\n    {\n        if (empty(self::\$class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        return self::\$class_mapper[\$class];\n    }\n\n    public function mapObject(\$object)\n    {\n        \$class = get_class(\$object);\n        if (empty(self::\$class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        return self::\$class_mapper[\$class];\n    }\n\n";
            foreach($docs as $doc) {
                echo "\n    /**\n     *  Validate " . ($doc['class']) . " object\n     */\n    public function validate_" . (sha1($doc['class'])) . "(\\" . ($doc['class']) . " \$object)\n    {\n";
                foreach($doc['annotation']->getProperties() as $prop) {
                    echo "            /** " . ($prop['property']) . " */\n";
                    if (in_array('public', $prop['visibility'])) {
                        echo "            if (\$object->" . ($prop['property']) . ") {\n                \$data = \$object->" . ($prop['property']) . ";\n            } else {\n";
                        if ($prop->has('Required')) {
                            echo "                throw new \\RuntimeException(\"{\$prop['property']} cannot be empty\");\n";
                        }
                        else {
                            echo "                \$data = NULL;\n";
                        }
                        echo "            }\n";
                    }
                    else {
                        echo "            \$property = new \\ReflectionProperty(\$object, \"" . ( $prop['property'] ) . "\");\n            \$property->setAccessible(true);\n            \$data = \$property->getValue(\$object);\n";
                        if ($prop->has('Required')) {
                            echo "            if (empty(\$data)) {\n                throw new \\RuntimeException(\"{\$prop['property']} cannot be empty\");\n            }\n";
                        }
                    }
                    echo "\n";
                    foreach($validators as $name => $callback) {
                        if ($prop->has($name)) {
                            echo "            if (\$data && !" . ($callback) . "(\$data)) {\n                throw new \\RuntimeException(\"Validation failed for " . ($name) . "\");\n            }\n";
                        }
                    }
                    echo "\n";
                }
                echo "    }\n\n";
                foreach($events as $ev) {
                    echo "    /**\n     *  Code for " . ($ev) . " events for objects " . ($doc['class']) . "\n     */\n    public function trigger_" . ($ev) . "_" . (sha1($doc['class'])) . "(\$args)\n    {\n    }\n\n";
                }
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
