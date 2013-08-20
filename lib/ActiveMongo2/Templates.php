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
     *  Template class generated from Trigger.tpl.php
     */
    class class_11ca6999533bd9c460f246ff122fc6c9341f7a1f extends base_template_46ff768978a6897199daa478860b8cd25af655b1
    {

        public function render(Array $vars = array(), $return = false)
        {
            $this->context = $vars;

            extract($vars);
            if ($return) {
                ob_start();
            }
            if ($method->has($ev)) {
                if (in_array('public', $method['visibility'])) {
                    echo "        \$return = " . ($target) . "->" . ($method['function']) . "(\$document, \$args, \$this->connection, " . (var_export($method[0]['args'], true)) . ", \$this);\n";
                }
                else {
                    echo "        \$reflection = new ReflectionMethod(\"\\\\" . (addslashes($doc['class'])) . "\", \"" . ($method['function']) . "\");\n        \$return = \$reflection->invoke(\$document, " . ($target) . ", \$args, \$this->connection, " . (var_export($method[0]['args'], true)) . ", \$this);\n";
                }
                echo "    if (\$return === FALSE) {\n        throw new \\RuntimeException(\"" . (addslashes($doc['class']) . "::" . $method['function']) . " returned false\");\n    }\n";
            }
            echo "\n";

            if ($return) {
                return ob_get_clean();
            }

        }
    }

    /** 
     *  Template class generated from Validate.tpl.php
     */
    class class_9e8794c44ad8c1631f7e215c9edaf7dbac875fb4 extends base_template_46ff768978a6897199daa478860b8cd25af655b1
    {

        public function render(Array $vars = array(), $return = false)
        {
            $this->context = $vars;

            extract($vars);
            if ($return) {
                ob_start();
            }
            if (empty($var)) {
                $var = 'doc';
            }
            foreach($validators as $name => $callback) {
                if ($prop->has($name)) {
                    echo "        /* " . ($prop['property']) . " - " . ($name) . " " . ( '{{{' ) . " */\n        if (empty(\$this->loaded['" . ($files[$name]) . "'])) {\n            require_once '" . ($files[$name]) . "';\n            \$this->loaded['" . ($files[$name]) . "'] = true;\n        }\n        if (!empty(\$" . ($var) . "['" . ($propname) . "']) && !" . ($callback) . "(\$" . ($var) . "['" . ($propname) . "'], " . (var_export(($prop[0]['args']) ?: [],  true)) . ", \$this->connection, \$this)) {\n            throw new \\RuntimeException(\"Validation failed for " . ($name) . "\");\n        }\n        /* }}} */\n\n";
                }
            }

            if ($return) {
                return ob_get_clean();
            }

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
            echo "<?php\n\nnamespace ActiveMongo2\\Generated" . ($namespace) . ";\n\nuse ActiveMongo2\\Connection;\n\nclass Mapper\n{\n    protected \$mapper = " . ( var_export($mapper, true) ) . ";\n    protected \$class_mapper = " . ( var_export($class_mapper, true) ) . ";\n    protected \$loaded = array();\n    protected \$connection;\n\n    public function __construct(Connection \$conn)\n    {\n        \$this->connection = \$conn;\n    }\n\n    public function mapCollection(\$col)\n    {\n        if (empty(\$this->mapper[\$col])) {\n            throw new \\RuntimeException(\"Cannot map {\$col} collection to its class\");\n        }\n\n        \$data = \$this->mapper[\$col];\n\n        if (empty(\$this->loaded[\$data['file']])) {\n            require_once \$data['file'];\n            \$this->loaded[\$data['file']] = true;\n        }\n\n        return \$data;\n    }\n\n    public function mapClass(\$class)\n    {\n        if (empty(\$this->class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        \$data = \$this->class_mapper[\$class];\n\n        if (empty(\$this->loaded[\$data['file']])) {\n            require_once \$data['file'];\n            \$this->loaded[\$data['file']] = true;\n        }\n\n        return \$data;\n    }\n\n    public function mapObject(\$object)\n    {\n        \$class = get_class(\$object);\n        if (empty(\$this->class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        return \$this->class_mapper[\$class];\n    }\n\n    public function getDocument(\$object)\n    {\n        \$class = get_class(\$object);\n        if (empty(\$this->class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        return \$this->{\"get_array_\" . sha1(\$class)}(\$object);\n    }\n\n    public function validate(\$object)\n    {\n        \$class = get_class(\$object);\n        if (empty(\$this->class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        return \$this->{\"validate_\" . sha1(\$class)}(\$object);\n    }\n\n    public function update(\$object, Array \$doc, Array \$old)\n    {\n        \$class = get_class(\$object);\n        if (empty(\$this->class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        return \$this->{\"update_\" . sha1(\$class)}(\$doc, \$old);\n    }\n\n    public function populate(\$object, Array \$data)\n    {\n        \$class = get_class(\$object);\n        if (empty(\$this->class_mapper[\$class])) {\n            throw new \\RuntimeException(\"Cannot map class {\$class} to its document\");\n        }\n\n        return \$this->{\"populate_\" . sha1(\$class)}(\$object, \$data);\n    }\n\n    public function trigger(\$event, \$object, Array \$args = array())\n    {\n        \$class  = get_class(\$object);\n        \$method = \"event_{\$event}_\" . sha1(\$class);\n        if (!is_callable(array(\$this, \$method))) {\n            throw new \\RuntimeException(\"Cannot trigger {\$event} event on '\$class' objects\");\n        }\n\n        return \$this->\$method(\$object, \$args);\n    }\n\n    public function updateProperty(\$document, \$key, \$value)\n    {\n        \$class  = get_class(\$document);\n        \$method = \"update_property_\" . sha1(\$class);\n        if (!is_callable(array(\$this, \$method))) {\n            throw new \\RuntimeException(\"Cannot trigger {\$event} event on '\$class' objects\");\n        }\n\n        return \$this->\$method(\$document, \$key, \$value);\n    }\n\n    public function ensureIndex(\$db)\n    {\n";
            foreach($indexes as $index) {
                echo "            \$db->" . ($index[0]) . "->ensureIndex(" . (var_export($index[1], true)) . ", " . (var_export($index[2], true)) . ");\n";
            }
            echo "    }\n\n";
            foreach($docs as $doc) {
                echo "    /**\n     *  Get update object " . ($doc['class']) . " \n     */\n    public function update_" . (sha1($doc['class'])) . "(Array \$current, Array \$old, \$embed = false)\n    {\n        if (!\$embed && \$current['_id'] != \$old['_id']) {\n            throw new \\RuntimeException(\"document ids cannot be updated\");\n        }\n\n        \$change = array();\n\n";
                foreach($doc['annotation']->getProperties() as $prop) {
                    $propname = $prop['property'];
                    $var = 'current';
                    if ($prop->has('Id')) {
                        $propname = '_id';
                    }
                    echo "\n            if (array_key_exists('" . ($propname) . "', \$current)\n                || array_key_exists('" . ($propname) . "', \$old)) {\n\n                if (!array_key_exists('" . ($propname) . "', \$current)) {\n                    \$change['\$unset']['" . ($propname) . "'] = 1;\n                } else if (!array_key_exists('" . ($propname) . "', \$old)) {\n                    \$change['\$set']['" . ($propname) . "'] = \$current['" . ($propname) . "'];\n";
                    ActiveMongo2\Templates::exec('validate', compact('propname', 'validators', 'files', 'prop', 'var'), $this->context);
                    echo "                } else if (\$current['" . ($propname) . "'] !== \$old['" . ($propname) . "']) {\n";
                    if ($prop->has('Inc')) {
                        echo "                        if (empty(\$old['" . ($propname) . "'])) {\n                            \$prev = 0;\n                        } else {\n                            \$prev = \$old['" . ($propname) . "'];\n                        }\n                        \$change['\$inc']['" . ($propname) . "'] = \$current['" . ($propname) . "'] - \$prev;\n";
                    }
                    else if ($prop->has('Embed')) {
                        echo "                        if (\$current['" . ($propname) . "']['__embed_class'] != \$old['" . ($propname) . "']['__embed_class']) {\n                            \$change['\$set']['" . ($propname) . ".' . \$index] = \$current['" . ($propname) . "'];\n                        } else {\n                            \$update = 'update_' . sha1(\$current['" . ($propname) . "']['__embed_class']);\n                            \$diff = \$this->\$update(\$current['" . ($propname) . "'], \$old['" . ($propname) . "'], true);\n                            foreach (\$diff as \$op => \$value) {\n                                foreach (\$value as \$p => \$val) {\n                                    \$change[\$op]['" . ($propname) . ".' . \$p] = \$val;\n                                }\n                            }\n                        }\n";
                    }
                    else if ($prop->has('EmbedMany')) {
                        echo "                        foreach (\$current['" . ($propname) . "'] as \$index => \$value) {\n                            if (!array_key_exists(\$index, \$old['" . ($propname) . "'])) {\n                                \$change['\$push']['" . ($propname) . "'] = \$value;\n                                continue;\n                            }\n                            if (\$value['__embed_class'] != \$old['" . ($propname) . "'][\$index]['__embed_class']) {\n                                \$change['\$set']['" . ($propname) . ".' . \$index] = \$value;\n                            } else {\n                                \$update = 'update_' . sha1(\$value['__embed_class']);\n                                \$diff = \$this->\$update(\$value, \$old['" . ($propname) . "'][\$index], true);\n                                foreach (\$diff as \$op => \$value) {\n                                    foreach (\$value as \$p => \$val) {\n                                        \$change[\$op]['" . ($propname) . ".' . \$index . '.' . \$p] = \$val;\n                                    }\n                                }\n                            }\n                        }\n";
                    }
                    else {
                        echo "                        \$change['\$set']['" . ($propname) . "'] = \$current['" . ($propname) . "'];\n";
                        ActiveMongo2\Templates::exec('validate', compact('propname', 'validators', 'files', 'prop'), $this->context);
                    }


                    echo "                }\n            }\n";
                }
                echo "\n        return \$change;\n    }\n\n    /**\n     *  Populate objects " . ($doc['class']) . " \n     */\n    public function populate_" . (sha1($doc['class'])) . "(\\" . ($doc['class']) . " \$object, Array \$data)\n    {\n";
                foreach($doc['annotation']->getProperties() as $prop) {
                    $name = $prop['property'];
                    if ($prop->has('Id')) {
                        $name = '_id';
                    }
                    echo "            if (array_key_exists(\"" . ($name) . "\", \$data)) {\n";
                    foreach($hydratations as $zname => $callback) {
                        if ($prop->has($zname)) {
                            echo "                        if (empty(\$this->loaded['" . ($files[$zname]) . "'])) {\n                            require_once '" . ($files[$zname]) . "';\n                            \$this->loaded['" . ($files[$zname]) . "'] = true;\n                        }\n                        \n                        " . ($callback) . "(\$data['" . ($name) . "'], " . (var_export($prop[0]['args'] ?: [],  true)) . ", \$this->connection, \$this);\n";
                        }
                    }
                    echo "\n";
                    if (in_array('public', $prop['visibility'])) {
                        echo "                    \$object->" . ($prop['property']) . " = \$data['" . ($name) . "'];\n";
                    }
                    else {
                        echo "                    \$property = new \\ReflectionProperty(\$object, \"" . ( $prop['property'] ) . "\");\n                    \$property->setAccessible(true);\n                    \$property->setValue(\$object, \$data['" . ($name) . "']);\n";
                    }
                    echo "                \n            }\n";
                }
                echo "    }\n\n    /**\n     *  Validate " . ($doc['class']) . " object\n     */\n    public function get_array_" . (sha1($doc['class'])) . "(\\" . ($doc['class']) . " \$object)\n    {\n        \$doc = array();\n";
                foreach($doc['annotation']->getProperties() as $prop) {
                    echo "            /* " . ($prop['property']) . " " . ( '{{{' ) . " */\n";
                    $propname = $prop['property'];
                    if ($prop->has('Id')) {
                        $propname = '_id';
                    }
                    if (in_array('public', $prop['visibility'])) {
                        echo "                if (\$object->" . ($prop['property']) . " !== NULL) {\n                    \$doc['" . ($propname) . "'] = \$object->" . ($prop['property']) . ";\n                }\n";
                    }
                    else {
                        echo "                \$property = new \\ReflectionProperty(\$object, \"" . ( $prop['property'] ) . "\");\n                \$property->setAccessible(true);\n                \$doc['" . ($propname) . "'] = \$property->getValue(\$object);\n";
                    }
                    echo "            /* }}} */\n";
                }
                echo "        return \$doc;\n    }\n\n    /**\n     *  Validate " . ($doc['class']) . " object\n     */\n    public function validate_" . (sha1($doc['class'])) . "(\\" . ($doc['class']) . " \$object)\n    {\n        \$doc = \$this->get_array_" . (sha1($doc['class'])) . "(\$object);\n\n";
                foreach($doc['annotation']->getProperties() as $prop) {
                    $propname = $prop['property'];
                    if ($prop->has('Id')) {
                        $propname = '_id';
                    }
                    if ($prop->has('Required')) {
                        echo "            if (empty(\$doc['" . ($propname) . "'])) {\n                throw new \\RuntimeException(\"" . ($prop['property']) . " cannot be empty\");\n            }\n";
                    }
                    echo "\n";
                    ActiveMongo2\Templates::exec('validate', compact('propname', 'validators', 'files', 'prop'), $this->context);
                }
                echo "\n        return \$doc;\n    }\n\n    protected function update_property_" . (sha1($doc['class'])) . "(\\" . ($doc['class']) . " \$document, \$property, \$value)\n    {\n";
                foreach($doc['annotation']->getProperties() as $prop) {
                    $propname = $prop['property'];
                    echo "            if (\$property ==  '" . ($propname) . "'\n";
                    foreach($prop->getAll() as $annotation) {
                        echo "                 || \$property == '" . "@" . ($annotation['method']) . "'\n";
                    }
                    echo "            ) {\n";
                    if (in_array('public', $prop['visibility'])) {
                        echo "                    \$document->" . ($prop['property']) . " = \$value;\n";
                    }
                    else {
                        echo "                    \$property = new \\ReflectionProperty(\$object, \"" . ( $prop['property'] ) . "\");\n                    \$property->setAccessible(true);\n                    \$property->setValue(\$document, \$value);\n";
                    }
                    echo "            }\n";
                }
                echo "    }\n\n\n";
                foreach($events as $ev) {
                    echo "    /**\n     *  Code for " . ($ev) . " events for objects " . ($doc['class']) . "\n     */\n        protected function event_" . ($ev) . "_" . (sha1($doc['class'])) . "(\\" . ($doc['class']) . " \$document, Array \$args)\n        {\n";
                    foreach($doc['annotation']->getMethods() as $method) {
                        ActiveMongo2\Templates::exec("trigger", ['method' => $method, 'ev' => $ev, 'doc' => $doc, 'target' => '$document'], $this->context);
                    }
                    echo "\n";
                    foreach($doc['annotation']->getAll() as $zmethod) {
                        $first_time = false;
                        if (!empty($plugins[$zmethod['method']])) {
                            $temp = $plugins[$zmethod['method']];
                            foreach($temp->getMethods() as $method) {
                                if ($method->has($ev) && empty($first_time)) {
                                    echo "                            if (empty(\$this->loaded[\"" . ($temp['file']) . "\"])) {\n                                require_once \"" . ($temp['file']) . "\";\n                                \$this->loaded[\"" . ($temp['file']) . "\"] = true;\n                            }\n                            \$plugin = new \\" . ($temp['class']) . "(" . ( var_export($zmethod['args'], true) ) . ");\n";
                                    $first_time = true;
                                    ActiveMongo2\Templates::exec("trigger", ['method' => $method, 'ev' => $ev, 'doc' => $temp, 'target' => '$plugin'], $this->context);
                                }
                            }
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
                'trigger.tpl.php' => 'class_11ca6999533bd9c460f246ff122fc6c9341f7a1f',
                'trigger' => 'class_11ca6999533bd9c460f246ff122fc6c9341f7a1f',
                'validate.tpl.php' => 'class_9e8794c44ad8c1631f7e215c9edaf7dbac875fb4',
                'validate' => 'class_9e8794c44ad8c1631f7e215c9edaf7dbac875fb4',
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
